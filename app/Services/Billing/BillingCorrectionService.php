<?php

namespace App\Services\Billing;

use App\Models\Billing;
use App\Models\MemberLedger;
use App\Services\AuditLogService;
use App\Services\LedgerToolchainService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BillingCorrectionService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly LedgerToolchainService $ledgerToolchainService,
    ) {
    }

    public function correct(Billing $billing, float $newNetPayable, string $reason, int $userId): Billing
    {
        return DB::transaction(function () use ($billing, $newNetPayable, $reason, $userId) {
            $billing->refresh();

            if ($billing->billing_status !== 'POSTED') {
                throw new RuntimeException('Only posted billings can be corrected.');
            }

            $before = $billing->toArray();
            $delta = round($newNetPayable - (float) $billing->net_payable, 2);

            $billing->net_payable = $newNetPayable;
            $billing->correction_reason = $reason;
            $billing->generated_by_user_id = $userId;
            $billing->save();

            $existingCorrectionLedger = MemberLedger::query()
                ->where('member_id', $billing->member_id)
                ->where('ref_type', 'BILL_CORRECTION')
                ->where('ref_id', $billing->id)
                ->first();

            if ($existingCorrectionLedger) {
                $existingCorrectionLedger->delete();
            }

            $baseBalance = (float) (MemberLedger::query()
                ->where('member_id', $billing->member_id)
                ->where(function ($q) use ($billing) {
                    $q->where('ref_type', '!=', 'BILL_CORRECTION')
                        ->orWhere('ref_id', '!=', $billing->id);
                })
                ->orderByDesc('entry_date')
                ->orderByDesc('id')
                ->value('balance_after') ?? 0);

            if ($delta !== 0.0) {
                MemberLedger::query()->create([
                    'member_id' => $billing->member_id,
                    'entry_date' => now()->toDateString(),
                    'debit' => $delta > 0 ? abs($delta) : 0,
                    'credit' => $delta < 0 ? abs($delta) : 0,
                    'ref_type' => 'BILL_CORRECTION',
                    'ref_id' => $billing->id,
                    'balance_after' => round($baseBalance + $delta, 2),
                    'reason_code' => 'BILLING_CORRECTION',
                    'posted_by_user_id' => $userId,
                ]);
            }

            $this->ledgerToolchainService->recompute((int) $billing->member_id);

            $this->auditLogService->log('billing.corrected', Billing::class, (int) $billing->id, $before, $billing->toArray(), $reason);

            return $billing->fresh();
        });
    }
}
