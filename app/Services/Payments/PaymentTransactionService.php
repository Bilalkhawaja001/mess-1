<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentTransaction;
use App\Services\AuditLogService;
use RuntimeException;

class PaymentTransactionService
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentReconciliationService $paymentReconciliationService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function recordFromAttempt(Payment $payment, PaymentAttempt $attempt, array $payload, int $userId): PaymentTransaction
    {
        $idempotencyKey = $payload['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existing = PaymentTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        $status = (string) ($payload['status'] ?? Payment::STATUS_INITIATED);
        if ($attempt->amount <= 0) {
            throw new RuntimeException('Invalid amount for transaction.');
        }

        $txn = PaymentTransaction::query()->create([
            'payment_id' => $payment->id,
            'payment_attempt_id' => $attempt->id,
            'member_id' => $payment->member_id,
            'bill_id' => $payment->bill_id,
            'payment_method_id' => $payment->payment_method_id,
            'internal_ref' => 'TXN-'.now()->format('YmdHis').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'external_ref' => $payload['external_ref'] ?? null,
            'merchant_ref' => $payload['merchant_ref'] ?? null,
            'idempotency_key' => $idempotencyKey,
            'amount' => (float) $attempt->amount,
            'currency' => $attempt->currency,
            'status' => $status,
            'failure_reason' => $payload['failure_reason'] ?? null,
            'raw_request_summary' => $payload['raw_request_summary'] ?? null,
            'raw_response_summary' => $payload['raw_response_summary'] ?? null,
            'initiated_at' => now(),
            'completed_at' => in_array($status, [Payment::STATUS_SUCCESS, Payment::STATUS_FAILED], true) ? now() : null,
            'verified_at' => in_array($status, [Payment::STATUS_SUCCESS, Payment::STATUS_RECONCILED], true) ? now() : null,
            'created_by_user_id' => $userId,
        ]);

        $payment->last_transaction_id = $txn->id;
        $payment->save();

        if ($status === Payment::STATUS_SUCCESS) {
            $this->paymentService->transition($payment, Payment::STATUS_SUCCESS, 'transaction-success');
            if (! $payment->reconciliations()->exists()) {
                $this->paymentReconciliationService->createPending($payment, $txn);
            }
        } elseif (in_array($status, [Payment::STATUS_FAILED, Payment::STATUS_CANCELLED, Payment::STATUS_EXPIRED], true)) {
            $this->paymentService->transition($payment, $status, 'transaction-finalized');
        }

        $this->auditLogService->log('payment.transaction_recorded', PaymentTransaction::class, (int) $txn->id, [], $txn->toArray(), 'transaction-recorded');

        return $txn;
    }

    public function manualVerify(PaymentTransaction $txn, int $userId, bool $markSuccess = true): PaymentTransaction
    {
        $before = $txn->toArray();
        $txn->status = $markSuccess ? Payment::STATUS_SUCCESS : Payment::STATUS_FAILED;
        $txn->verified_at = now();
        $txn->save();

        $payment = $txn->payment;
        if ($payment) {
            $this->paymentService->transition($payment, $txn->status, 'admin-manual-verify');
            if ($txn->status === Payment::STATUS_SUCCESS && ! $payment->reconciliations()->exists()) {
                $this->paymentReconciliationService->createPending($payment, $txn);
            }
        }

        $this->auditLogService->log('payment.transaction_verified_manual', PaymentTransaction::class, (int) $txn->id, $before, $txn->toArray(), 'manual-verify');

        return $txn->fresh();
    }
}
