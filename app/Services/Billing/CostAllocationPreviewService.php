<?php

namespace App\Services\Billing;

use App\Models\MonthlyAttendance;
use App\Support\BusinessMonthCycle;
use Illuminate\Support\Facades\DB;

class CostAllocationPreviewService
{
    public function build(string $monthCycle, float $execWeight = 1.25, float $centWeight = 1.0): array
    {
        $cycle = BusinessMonthCycle::resolve($monthCycle);
        $from = $cycle['cycle_start']->toDateString();
        $to = $cycle['cycle_end']->toDateString();

        $purchaseTotal = $this->netPurchaseTotal($from, $to);
        $guestAmount = $this->approvedGuestAmount($from, $to);
        $balanceAfterGuest = round($purchaseTotal - $guestAmount, 2);

        $buckets = $this->attendanceBuckets($monthCycle);
        $execAtt = $buckets['executive'];
        $centAtt = $buckets['centralized'];
        $contractorAtt = $buckets['contractors'];
        $totalAttendance = $execAtt + $centAtt + $contractorAtt;

        $flatPerDay = $totalAttendance > 0 ? round($balanceAfterGuest / $totalAttendance, 6) : 0.0;
        $contractorAmount = round($flatPerDay * $contractorAtt, 2);

        $messPool = round($balanceAfterGuest - $contractorAmount, 2);
        $memberHalf = round($messPool / 2, 2);
        $companyHalf = round($messPool - $memberHalf, 2);

        $weightedUnits = ($execAtt * $execWeight) + ($centAtt * $centWeight);
        $perUnit = $weightedUnits > 0 ? round($memberHalf / $weightedUnits, 6) : 0.0;
        $execRatePerDay = round($perUnit * $execWeight, 4);
        $centRatePerDay = round($perUnit * $centWeight, 4);

        $memberRows = $this->memberRows($monthCycle, $execRatePerDay, $centRatePerDay);
        $memberPreviewTotal = round(array_sum(array_column($memberRows, 'amount')), 2);

        $companyPayable = round($companyHalf + $guestAmount + $contractorAmount, 2);

        $reconTarget = round($memberHalf + $companyHalf + $contractorAmount + $guestAmount, 2);
        $reconPass = abs($reconTarget - round($purchaseTotal, 2)) < 0.05;

        return [
            'monthCycle' => $monthCycle,
            'from' => $from,
            'to' => $to,
            'execWeight' => $execWeight,
            'centWeight' => $centWeight,
            'purchaseTotal' => round($purchaseTotal, 2),
            'guestAmount' => $guestAmount,
            'balanceAfterGuest' => $balanceAfterGuest,
            'execAtt' => $execAtt,
            'centAtt' => $centAtt,
            'contractorAtt' => $contractorAtt,
            'totalAttendance' => $totalAttendance,
            'flatPerDay' => $flatPerDay,
            'contractorAmount' => $contractorAmount,
            'messPool' => $messPool,
            'memberHalf' => $memberHalf,
            'companyHalf' => $companyHalf,
            'weightedUnits' => $weightedUnits,
            'perUnit' => $perUnit,
            'execRatePerDay' => $execRatePerDay,
            'centRatePerDay' => $centRatePerDay,
            'memberRows' => $memberRows,
            'memberPreviewTotal' => $memberPreviewTotal,
            'companyPayable' => $companyPayable,
            'reconTarget' => $reconTarget,
            'reconPass' => $reconPass,
        ];
    }

    private function netPurchaseTotal(string $from, string $to): float
    {
        $returnAgg = DB::table('vendor_returns')
            ->selectRaw('goods_receipt_line_id, SUM(qty_returned * unit_cost) as returned_cost')
            ->whereNotNull('goods_receipt_line_id')
            ->groupBy('goods_receipt_line_id');

        $netCostSql = '((goods_receipt_lines.qty_received * goods_receipt_lines.unit_cost) - COALESCE(vr.returned_cost, 0))';

        return round((float) DB::table('goods_receipt_lines')
            ->leftJoinSub($returnAgg, 'vr', fn ($j) => $j->on('vr.goods_receipt_line_id', '=', 'goods_receipt_lines.id'))
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_lines.goods_receipt_id')
            ->whereBetween('goods_receipts.received_date', [$from, $to])
            ->selectRaw("COALESCE(SUM($netCostSql), 0) as total_cost")
            ->value('total_cost'), 2);
    }

    private function approvedGuestAmount(string $from, string $to): float
    {
        return round((float) DB::table('guest_meals')
            ->whereNotNull('approved_at')
            ->whereDate('meal_date', '>=', $from)
            ->whereDate('meal_date', '<=', $to)
            ->sum('amount'), 2);
    }

    private function attendanceBuckets(string $monthCycle): array
    {
        $buckets = ['contractors' => 0, 'executive' => 0, 'centralized' => 0];
        $rows = MonthlyAttendance::query()->with('member.mess')->where('month_cycle', $monthCycle)->get();
        foreach ($rows as $row) {
            $b = $this->normalizeBucket((string) ($row->member?->mess?->code ?: $row->member?->mess?->name ?: ''));
            if ($b === null) continue;
            $buckets[$b] += (int) $row->present_days;
        }
        return $buckets;
    }

    private function memberRows(string $monthCycle, float $execRate, float $centRate): array
    {
        $out = [];
        $rows = MonthlyAttendance::query()->with('member.mess')->where('month_cycle', $monthCycle)->get();
        foreach ($rows as $row) {
            $code = (string) ($row->member?->mess?->code ?: $row->member?->mess?->name ?: '');
            $b = $this->normalizeBucket($code);
            if ($b !== 'executive' && $b !== 'centralized') continue;
            $rate = $b === 'executive' ? $execRate : $centRate;
            $days = (int) $row->present_days;
            $out[] = [
                'member' => (string) ($row->member?->name ?? ''),
                'member_code' => (string) ($row->member?->member_code ?? ''),
                'mess' => strtoupper($b),
                'present_days' => $days,
                'rate' => $rate,
                'amount' => round($days * $rate, 2),
            ];
        }
        return $out;
    }

    private function normalizeBucket(string $code): ?string
    {
        return match (strtoupper(trim($code))) {
            'CONTRACTOR', 'CONTRACTORS' => 'contractors',
            'EXEC', 'EXECUTIVE' => 'executive',
            'CENTRAL', 'CENTRALIZE', 'CENTRALIZED' => 'centralized',
            default => null,
        };
    }
}
