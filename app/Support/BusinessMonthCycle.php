<?php

namespace App\Support;

use Carbon\Carbon;
use InvalidArgumentException;

class BusinessMonthCycle
{
    public static function resolve(string $monthCycle): array
    {
        $month = Carbon::createFromFormat('!Y-m', $monthCycle);
        if (! $month || $month->format('Y-m') !== $monthCycle) {
            throw new InvalidArgumentException("Invalid month_cycle [{$monthCycle}]. Expected YYYY-MM.");
        }

        $month = $month->startOfMonth();
        $daysInMonth = $month->daysInMonth;

        // Business month closes 5 days before calendar month-end.
        $cycleEnd = $month->copy()
            ->endOfMonth()
            ->subDays(5)
            ->startOfDay();

        // Next cycle starts one day after previous month's close.
        $previousClose = $month->copy()
            ->subMonthNoOverflow()
            ->endOfMonth()
            ->subDays(5)
            ->startOfDay();

        $cycleStart = $previousClose->copy()->addDay()->startOfDay();

        return [
            'month_cycle' => $monthCycle,
            'cycle_start' => $cycleStart,
            'cycle_end' => $cycleEnd,
            'cycle_start_date' => $cycleStart->toDateString(),
            'cycle_end_date' => $cycleEnd->toDateString(),
            'cycle_days' => (int) $daysInMonth,
        ];
    }

    public static function defaultDashboardMonthCycle(?Carbon $today = null): string
    {
        return ($today ?: now())->copy()->subMonthNoOverflow()->format('Y-m');
    }
}
