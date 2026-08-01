<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentTransaction;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentTransactionService
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentReconciliationService $paymentReconciliationService,
        private readonly AuditLogService $auditLogService,
        private readonly PaymentDuplicateGuard $duplicateGuard,
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
            $this->paymentReconciliationService->createPending($payment, $txn);
        } elseif (in_array($status, [Payment::STATUS_FAILED, Payment::STATUS_CANCELLED, Payment::STATUS_EXPIRED], true)) {
            $this->paymentService->transition($payment, $status, 'transaction-finalized');
        }

        $this->auditLogService->log('payment.transaction_recorded', PaymentTransaction::class, (int) $txn->id, [], $txn->toArray(), 'transaction-recorded');

        return $txn;
    }

    public function manualVerify(PaymentTransaction $txn, int $userId, bool $markSuccess = true): PaymentTransaction
    {
        return DB::transaction(function () use ($txn, $userId, $markSuccess) {
            $lockedTxn = PaymentTransaction::query()->whereKey($txn->id)->lockForUpdate()->firstOrFail();
            $before = $lockedTxn->toArray();
            $lockedTxn->status = $markSuccess ? Payment::STATUS_SUCCESS : Payment::STATUS_FAILED;
            $lockedTxn->verified_at = now();
            $lockedTxn->completed_at = $lockedTxn->completed_at ?: now();
            $lockedTxn->save();

            $payment = $lockedTxn->payment
                ? Payment::query()->whereKey($lockedTxn->payment->id)->lockForUpdate()->first()
                : null;

            if ($payment) {
                if ($markSuccess) {
                    $bill = $this->duplicateGuard->lockBill((int) $payment->bill_id, (int) $payment->member_id);
                    $monthCycle = (string) $bill->month_cycle;
                    $this->duplicateGuard->assertNoActiveDuplicate((int) $payment->member_id, $monthCycle, (int) $payment->id, (float) $payment->amount);
                    $this->duplicateGuard->applyGuardAttributes($payment, Payment::STATUS_SUCCESS, $monthCycle)->save();
                }

                if (! in_array($payment->status, [Payment::STATUS_SUCCESS, Payment::STATUS_RECONCILED], true)) {
                    $this->paymentService->transition($payment, $lockedTxn->status, 'admin-manual-verify');
                }
                if ($lockedTxn->status === Payment::STATUS_SUCCESS && ! $payment->reconciliations()->exists()) {
                    $this->paymentReconciliationService->createPending($payment, $lockedTxn);
                }
            }

            $this->auditLogService->log('payment.transaction_verified_manual', PaymentTransaction::class, (int) $lockedTxn->id, $before, $lockedTxn->toArray(), 'manual-verify');

            return $lockedTxn->fresh();
        });
    }
}
