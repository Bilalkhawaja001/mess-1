<?php

namespace App\Services;

use App\Models\Payment;

class PaymentEditService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function edit(Payment $payment, array $payload, int $userId, string $reason): bool
    {
        if (in_array($payment->status, [Payment::STATUS_APPROVED, Payment::STATUS_SUCCESS, Payment::STATUS_RECONCILED], true)) {
            return false;
        }

        $before = $payment->toArray();

        $payment->fill($payload);
        $payment->edited_by_user_id = $userId;
        $payment->edited_at = now();
        $payment->edit_reason = $reason;
        $payment->save();

        $this->auditLogService->log('payment.edited', Payment::class, (int) $payment->id, $before, $payment->toArray(), $reason);

        return true;
    }
}
