<?php

namespace App\Services\Billing;

use App\Models\Billing;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class BillingCorrectionService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function correct(Billing $billing, float $newNetPayable, string $reason, int $userId): Billing
    {
        return DB::transaction(function () use ($billing, $newNetPayable, $reason, $userId) {
            $before = $billing->toArray();

            $billing->net_payable = $newNetPayable;
            $billing->billing_status = 'POSTED';
            $billing->correction_reason = $reason;
            $billing->generated_by_user_id = $userId;
            $billing->save();

            $this->auditLogService->log('billing.corrected', Billing::class, (int) $billing->id, $before, $billing->toArray(), $reason);

            return $billing;
        });
    }
}
