<?php

namespace App\Services\Billing;

use App\Models\Attendance;
use App\Models\Billing;
use App\Models\BillingCycle;
use App\Models\BillingRun;
use App\Models\Extra;
use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\MonthClosure;
use App\Models\MonthlyAttendance;
use App\Models\RatePolicy;
use App\Support\BusinessMonthCycle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BillingGenerationService
{
    private function configHash(string $monthCycle): string
    {
        $rates = RatePolicy::query()
            ->where('is_active', true)
            ->orderBy('rate_type')
            ->orderBy('effective_from')
            ->get([
                'rate_type',
                'value',
                'effective_from',
                'effective_to',
                'is_active',
                'approved_at',
            ])
            ->toArray();

        $attendance = MonthlyAttendance::query()
            ->where('month_cycle', $monthCycle)
            ->orderBy('member_id')
            ->get(['member_id', 'present_days'])
            ->toArray();

        return hash('sha256', json_encode([
            'rates' => $rates,
            'monthly_attendance' => $attendance,
        ], JSON_UNESCAPED_SLASHES));
    }

    private function scopeHash(string $monthCycle, array $memberIds, string $configHash): string
    {
        sort($memberIds);

        return hash('sha256', json_encode([
            'month_cycle' => $monthCycle,
            'member_ids' => $memberIds,
            'config_hash' => $configHash,
        ], JSON_UNESCAPED_SLASHES));
    }

    public function generate(string $monthCycle, int $actorId): array
    {
        $cycle = BusinessMonthCycle::resolve($monthCycle);
        $cycleStart = $cycle['cycle_start_date'];
        $cycleEnd = $cycle['cycle_end_date'];
        $calendarStart = $monthCycle . '-01';

        return DB::transaction(function () use ($monthCycle, $actorId, $cycleStart, $cycleEnd, $calendarStart) {
            $closure = MonthClosure::query()->where('month_cycle', $monthCycle)->latest('id')->first();
            if ($closure && $closure->status === MonthClosure::STATUS_CLOSED) {
                throw new RuntimeException("Month {$monthCycle} is closed. Reopen before billing generation.");
            }

            $cycle = BillingCycle::query()->updateOrCreate(
                ['month_cycle' => $monthCycle],
                ['status' => 'OPEN', 'is_closed' => false]
            );

            if ((bool) $cycle->is_closed) {
                throw new RuntimeException("Billing cycle {$monthCycle} is marked closed.");
            }

            $members = Member::query()
                ->with('mess')
                ->whereDate('join_date', '<=', $cycleEnd)
                ->where(function ($q) use ($cycleStart) {
                    $q->whereNull('leave_date')->orWhereDate('leave_date', '>=', $cycleStart);
                })
                ->orderBy('id')
                ->get();

            $memberIds = $members->pluck('id')->all();
            $configHash = $this->configHash($monthCycle);
            $scopeHash = $this->scopeHash($monthCycle, $memberIds, $configHash);

            $existingRun = BillingRun::query()
                ->where('month_cycle', $monthCycle)
                ->where('scope_hash', $scopeHash)
                ->first();

            if ($existingRun) {
                return [
                    'status' => 'already_generated',
                    'month_cycle' => $monthCycle,
                    'scope_hash' => $scopeHash,
                    'inserted' => $existingRun->inserted_count,
                    'skipped' => $existingRun->skipped_count,
                ];
            }

            $monthlySnapshots = MonthlyAttendance::query()->where('month_cycle', $monthCycle)->get()->keyBy('member_id');

            $inserted = 0;
            $skipped = 0;

            foreach ($members as $member) {
                $existingBill = Billing::query()
                    ->where('month_cycle', $monthCycle)
                    ->where('member_id', $member->id)
                    ->where('billing_status', 'POSTED')
                    ->first();

                if ($existingBill) {
                    $skipped++;
                    continue;
                }

                $cycleStartAt = Carbon::parse($cycleStart)->startOfDay();
                $cycleEndAt = Carbon::parse($cycleEnd)->startOfDay();
                $memberJoinAt = Carbon::parse((string) $member->join_date)->startOfDay();
                $memberLeaveAt = $member->leave_date
                    ? Carbon::parse((string) $member->leave_date)->startOfDay()
                    : null;

                $effectiveStartAt = $cycleStartAt->greaterThan($memberJoinAt) ? $cycleStartAt->copy() : $memberJoinAt->copy();
                $effectiveEndAt = $memberLeaveAt && $memberLeaveAt->lessThan($cycleEndAt) ? $memberLeaveAt->copy() : $cycleEndAt->copy();
                $employmentWindowDays = $effectiveEndAt->greaterThanOrEqualTo($effectiveStartAt)
                    ? ((int) $effectiveStartAt->diffInDays($effectiveEndAt) + 1)
                    : 0;

                $monthly = $monthlySnapshots->get($member->id);
                if ($monthly) {
                    $presentDays = (int) $monthly->present_days;
                    if ($presentDays < 0) {
                        throw new RuntimeException("Invalid monthly attendance for member {$member->member_code}: present_days must be >= 0");
                    }
                    if ($presentDays > $employmentWindowDays) {
                        throw new RuntimeException("Invalid monthly attendance for member {$member->member_code} in {$monthCycle}: present_days={$presentDays} exceeds valid employment-window days={$employmentWindowDays}");
                    }
                } else {
                    $presentDays = $employmentWindowDays > 0
                        ? Attendance::query()
                            ->where('member_id', $member->id)
                            ->whereBetween('attendance_date', [$effectiveStartAt->toDateString(), $effectiveEndAt->toDateString()])
                            ->where('present', true)
                            ->count()
                        : 0;
                }

                $ratePerDay = $this->resolveRatePerDay($member, $calendarStart);
                $base = round($presentDays * $ratePerDay, 2);
                $extras = (float) Extra::query()
                    ->where('member_id', $member->id)
                    ->whereBetween('extra_date', [$cycleStart, $cycleEnd])
                    ->sum('amount');
                $net = round($base + $extras, 2);

                $billing = Billing::query()->create([
                    'month_cycle' => $monthCycle,
                    'member_id' => $member->id,
                    'active_days' => $presentDays,
                    'rate_per_day' => $ratePerDay,
                    'base_amount' => $base,
                    'extras_amount' => $extras,
                    'net_payable' => $net,
                    'is_locked' => true,
                    'generated_by_user_id' => $actorId,
                    'billing_status' => 'POSTED',
                ]);

                $lastBal = (float) (MemberLedger::query()
                    ->where('member_id', $member->id)
                    ->orderByDesc('entry_date')
                    ->orderByDesc('id')
                    ->value('balance_after') ?? 0);

                MemberLedger::query()->create([
                    'member_id' => $member->id,
                    'entry_date' => $cycleEnd,
                    'debit' => $net,
                    'credit' => 0,
                    'ref_type' => 'BILL',
                    'ref_id' => $billing->id,
                    'balance_after' => round($lastBal + $net, 2),
                    'reason_code' => 'BILLING_GENERATE',
                    'posted_by_user_id' => $actorId,
                ]);

                $inserted++;
            }

            BillingRun::query()->create([
                'month_cycle' => $monthCycle,
                'scope_hash' => $scopeHash,
                'config_hash' => $configHash,
                'status' => 'DONE',
                'inserted_count' => $inserted,
                'skipped_count' => $skipped,
                'created_by_user_id' => $actorId,
            ]);

            return [
                'status' => 'generated',
                'month_cycle' => $monthCycle,
                'scope_hash' => $scopeHash,
                'inserted' => $inserted,
                'skipped' => $skipped,
            ];
        });
    }

    private function resolveRatePerDay(Member $member, string $startDate): float
    {
        $messCode = strtoupper((string) ($member->mess?->code ?: $member->mess?->name ?: ''));

        $rateType = match ($messCode) {
            'EXECUTIVE' => 'RATE_PER_DAY_EXECUTIVE',
            'CENTRALIZED', 'CENTRALIZE', 'CENTRAL' => 'RATE_PER_DAY_CENTRALIZED',
            'CONTRACTORS', 'CONTRACTOR' => 'RATE_PER_DAY_CONTRACTORS',
            default => 'PER_DAY',
        };

        $rate = RatePolicy::query()
            ->where('is_active', true)
            ->where('rate_type', $rateType)
            ->whereDate('effective_from', '<=', $startDate)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>', $startDate);
            })
            ->whereNotNull('approved_at')
            ->orderByDesc('effective_from')
            ->value('value');

        if ($rate !== null) {
            return (float) $rate;
        }

        if ($rateType === 'PER_DAY') {
            $fallback = (float) env('MESS_RATE_PER_DAY', 100.0);
            if ($fallback < 0) {
                throw new RuntimeException('MESS_RATE_PER_DAY cannot be negative');
            }

            return $fallback;
        }

        throw new RuntimeException("No approved active {$rateType} rate found for {$startDate}.");
    }
}
