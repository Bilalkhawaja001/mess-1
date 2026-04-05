<?php

namespace App\Services;

use App\Models\Billing;
use App\Models\BillingCycle;
use App\Models\BillingRun;
use App\Models\MemberLedger;
use App\Models\MonthClosure;
use Illuminate\Support\Facades\DB;

class MonthClosureService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly LedgerToolchainService $ledgerToolchainService,
    ) {
    }

    public function close(string $monthCycle, int $userId, string $reason): void
    {
        DB::transaction(function () use ($monthCycle, $userId, $reason) {
            Billing::query()->where('month_cycle', $monthCycle)->update(['is_locked' => true]);

            BillingCycle::query()->updateOrCreate(
                ['month_cycle' => $monthCycle],
                [
                    'status' => MonthClosure::STATUS_CLOSED,
                    'is_closed' => true,
                    'closed_at' => now(),
                    'closed_by_user_id' => $userId,
                    'close_reason' => $reason,
                ]
            );

            MonthClosure::query()->updateOrCreate(
                ['month_cycle' => $monthCycle],
                ['status' => MonthClosure::STATUS_CLOSED, 'closed_by_user_id' => $userId, 'closed_at' => now(), 'reason' => $reason]
            );

            $this->auditLogService->log('month.closed', MonthClosure::class, null, [], ['month_cycle' => $monthCycle], $reason);
        });
    }

    public function reopen(string $monthCycle, int $userId, string $reason): void
    {
        DB::transaction(function () use ($monthCycle, $userId, $reason) {
            Billing::query()->where('month_cycle', $monthCycle)->update(['is_locked' => false]);

            BillingCycle::query()->updateOrCreate(
                ['month_cycle' => $monthCycle],
                [
                    'status' => MonthClosure::STATUS_OPEN,
                    'is_closed' => false,
                    'closed_at' => null,
                    'closed_by_user_id' => null,
                    'close_reason' => null,
                ]
            );

            MonthClosure::query()->updateOrCreate(
                ['month_cycle' => $monthCycle],
                ['status' => MonthClosure::STATUS_OPEN, 'reopened_by_user_id' => $userId, 'reopened_at' => now(), 'reason' => $reason]
            );

            $this->auditLogService->log('month.reopened', MonthClosure::class, null, [], ['month_cycle' => $monthCycle], $reason);
        });
    }

    public function hardReset(string $monthCycle, int $userId, string $reason): void
    {
        DB::transaction(function () use ($monthCycle, $userId, $reason) {
            $billingIds = Billing::query()->where('month_cycle', $monthCycle)->pluck('id');

            $affectedMemberIds = Billing::query()->where('month_cycle', $monthCycle)->pluck('member_id')->unique()->values();

            MemberLedger::query()
                ->where(function ($q) use ($billingIds) {
                    $q->where(function ($inner) use ($billingIds) {
                        $inner->where('ref_type', 'BILL')->whereIn('ref_id', $billingIds);
                    })->orWhere(function ($inner) use ($billingIds) {
                        $inner->where('ref_type', 'BILL_CORRECTION')->whereIn('ref_id', $billingIds);
                    });
                })
                ->delete();

            Billing::query()->where('month_cycle', $monthCycle)->delete();
            BillingRun::query()->where('month_cycle', $monthCycle)->delete();

            BillingCycle::query()->updateOrCreate(
                ['month_cycle' => $monthCycle],
                [
                    'status' => MonthClosure::STATUS_OPEN,
                    'is_closed' => false,
                    'closed_at' => null,
                    'closed_by_user_id' => null,
                    'close_reason' => null,
                ]
            );

            MonthClosure::query()->updateOrCreate(
                ['month_cycle' => $monthCycle],
                ['status' => MonthClosure::STATUS_HARD_RESET, 'hard_reset_by_user_id' => $userId, 'hard_reset_at' => now(), 'reason' => $reason]
            );

            foreach ($affectedMemberIds as $affectedMemberId) {
                $this->ledgerToolchainService->recompute((int) $affectedMemberId);
            }

            $this->auditLogService->log('month.hard_reset', MonthClosure::class, null, [], ['month_cycle' => $monthCycle], $reason);
        });
    }
}
