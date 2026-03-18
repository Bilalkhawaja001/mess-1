<?php

namespace App\Services;

use App\Models\Billing;
use App\Models\MonthClosure;
use Illuminate\Support\Facades\DB;

class MonthClosureService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function close(string $monthCycle, int $userId, string $reason): void
    {
        DB::transaction(function () use ($monthCycle, $userId, $reason) {
            Billing::query()->where('month_cycle', $monthCycle)->update(['is_locked' => true]);

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
            Billing::query()->where('month_cycle', $monthCycle)->delete();

            MonthClosure::query()->updateOrCreate(
                ['month_cycle' => $monthCycle],
                ['status' => MonthClosure::STATUS_HARD_RESET, 'hard_reset_by_user_id' => $userId, 'hard_reset_at' => now(), 'reason' => $reason]
            );

            $this->auditLogService->log('month.hard_reset', MonthClosure::class, null, [], ['month_cycle' => $monthCycle], $reason);
        });
    }
}
