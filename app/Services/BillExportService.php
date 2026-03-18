<?php

namespace App\Services;

use App\Models\Billing;

class BillExportService
{
    public function exportRows(?string $monthCycle): array
    {
        $query = Billing::query()->with('member')->orderBy('month_cycle')->orderBy('member_id');
        if ($monthCycle) {
            $query->where('month_cycle', $monthCycle);
        }

        $rows = [];
        foreach ($query->get() as $billing) {
            $rows[] = [
                'month_cycle' => $billing->month_cycle,
                'member_code' => $billing->member?->member_code,
                'member_name' => $billing->member?->name,
                'active_days' => $billing->active_days,
                'rate_per_day' => $billing->rate_per_day,
                'base_amount' => $billing->base_amount,
                'extras_amount' => $billing->extras_amount,
                'net_payable' => $billing->net_payable,
            ];
        }

        return $rows;
    }
}
