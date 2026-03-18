<?php

namespace App\Services\Billing;

use App\Models\Attendance;
use App\Models\Billing;
use App\Models\BillingCycle;
use App\Models\BillingRun;
use App\Models\Extra;
use App\Models\Member;
use App\Models\MemberLedger;
use App\Models\RatePolicy;
use Illuminate\Support\Facades\DB;

class BillingGenerationService
{
    private function configHash(): string
    {
        $rates = RatePolicy::query()->where('is_active', true)->orderBy('rate_type')->orderBy('effective_from')->get([
            'rate_type','value','effective_from','effective_to','is_active','approved_at'
        ])->toArray();

        return hash('sha256', json_encode($rates, JSON_UNESCAPED_SLASHES));
    }

    private function scopeHash(string $monthCycle, array $memberIds, string $configHash): string
    {
        sort($memberIds);
        return hash('sha256', json_encode([
            'month_cycle'=>$monthCycle,
            'member_ids'=>$memberIds,
            'config_hash'=>$configHash,
        ], JSON_UNESCAPED_SLASHES));
    }

    public function generate(string $monthCycle, int $actorId, float $fallbackRatePerDay = 100): array
    {
        [$year, $month] = array_map('intval', explode('-', $monthCycle));
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));

        return DB::transaction(function () use ($monthCycle, $actorId, $fallbackRatePerDay, $start, $end) {
            BillingCycle::query()->updateOrCreate(
                ['month_cycle' => $monthCycle],
                ['status' => 'OPEN', 'is_closed' => false]
            );

            $members = Member::query()
                ->where('is_active', true)
                ->whereDate('join_date', '<=', $end)
                ->where(function ($q) use ($start) {
                    $q->whereNull('leave_date')->orWhereDate('leave_date', '>=', $start);
                })->get();

            $memberIds = $members->pluck('id')->all();
            $configHash = $this->configHash();
            $scopeHash = $this->scopeHash($monthCycle, $memberIds, $configHash);

            $existingRun = BillingRun::query()->where('month_cycle', $monthCycle)->where('scope_hash', $scopeHash)->first();
            if ($existingRun) {
                return [
                    'status'=>'already_generated',
                    'month_cycle'=>$monthCycle,
                    'scope_hash'=>$scopeHash,
                    'inserted'=>$existingRun->inserted_count,
                    'skipped'=>$existingRun->skipped_count,
                ];
            }

            $inserted = 0; $skipped = 0;

            $rate = RatePolicy::query()
                ->where('is_active', true)
                ->where('rate_type', 'PER_DAY')
                ->whereDate('effective_from', '<=', $end)
                ->where(function($q) use ($start){ $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $start); })
                ->orderByDesc('effective_from')
                ->value('value');
            $ratePerDay = $rate !== null ? (float)$rate : $fallbackRatePerDay;

            foreach ($members as $member) {
                $exists = Billing::query()->where('month_cycle', $monthCycle)->where('member_id', $member->id)->first();
                if ($exists) { $skipped++; continue; }

                $presentDays = Attendance::query()->where('member_id', $member->id)
                    ->whereBetween('attendance_date', [$start, $end])->where('present', true)->count();

                $base = round($presentDays * $ratePerDay, 2);
                $extras = (float) Extra::query()->where('member_id', $member->id)
                    ->whereBetween('extra_date', [$start, $end])->sum('amount');
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
                ]);

                $lastBal = (float) (MemberLedger::query()->where('member_id', $member->id)
                    ->orderByDesc('entry_date')->orderByDesc('id')->value('balance_after') ?? 0);

                MemberLedger::query()->create([
                    'member_id'=>$member->id,
                    'entry_date'=>$end,
                    'debit'=>$net,
                    'credit'=>0,
                    'ref_type'=>'BILL',
                    'ref_id'=>$billing->id,
                    'balance_after'=>round($lastBal + $net,2),
                    'reason_code'=>'BILLING_GENERATE',
                    'posted_by_user_id'=>$actorId,
                ]);

                $inserted++;
            }

            BillingRun::query()->create([
                'month_cycle'=>$monthCycle,
                'scope_hash'=>$scopeHash,
                'config_hash'=>$configHash,
                'status'=>'DONE',
                'inserted_count'=>$inserted,
                'skipped_count'=>$skipped,
                'created_by_user_id'=>$actorId,
            ]);

            return ['status'=>'generated','month_cycle'=>$monthCycle,'scope_hash'=>$scopeHash,'inserted'=>$inserted,'skipped'=>$skipped];
        });
    }
}
