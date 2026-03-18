<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentMethod;
use App\Services\AuditLogService;

class PaymentAttemptService
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function createAttempt(int $memberId, int $billId, int $methodId, float $amount, int $userId): array
    {
        $method = PaymentMethod::query()->whereKey($methodId)->where('is_active', true)->firstOrFail();

        $payment = $this->paymentService->createOrGetPending($memberId, $billId, $methodId, $amount, $userId);
        if ($payment->status === Payment::STATUS_PENDING) {
            $this->paymentService->transition($payment, Payment::STATUS_INITIATED, 'attempt-created');
        }

        $attempt = PaymentAttempt::query()->create([
            'payment_id' => $payment->id,
            'member_id' => $memberId,
            'bill_id' => $billId,
            'payment_method_id' => $methodId,
            'attempt_ref' => 'ATT-'.now()->format('YmdHis').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'amount' => $amount,
            'currency' => 'PKR',
            'status' => Payment::STATUS_INITIATED,
            'audit_payload' => [
                'channel' => 'internal',
                'method_code' => $method->code,
                'mode' => 'no-live-charge',
            ],
            'created_by_user_id' => $userId,
        ]);

        $payment->last_attempt_id = $attempt->id;
        $payment->save();

        $this->auditLogService->log('payment.attempt_created', PaymentAttempt::class, (int) $attempt->id, [], $attempt->toArray(), 'attempt-created');

        return [$payment->fresh(), $attempt];
    }
}
