<?php

namespace App\Support;

use Carbon\Carbon;
use InvalidArgumentException;

class BusinessMonthCycle
{
    public static function resolve(string $monthCycle): array
    {
        $month = Carbon::createFromFormat('Y-m', $monthCycle);
        if (! $month || $month->format('Y-m') !== $monthCycle) {
            throw new InvalidArgumentException("Invalid month_cycle [{$monthCycle}]. Expected YYYY-MM.");
        }

        $month = $month->startOfMonth();
        $daysInMonth = $month->daysInMonth;

        [$startDay, $endDay] = match ($daysInMonth) {
            31 => [26, 26],
            30 => [26, 25],
            29 => [27, 24],
            28 => [27, 23],
            default => throw new InvalidArgumentException("Unsupported business cycle month length [{$daysInMonth}] for {$monthCycle}."),
        };

        $cycleStart = $month->copy()->subMonthNoOverflow()->setDay($startDay)->startOfDay();
        $cycleEnd = $month->copy()->setDay($endDay)->endOfDay();

        return [
            'month_cycle' => $monthCycle,
            'cycle_start' => $cycleStart,
            'cycle_end' => $cycleEnd,
            'cycle_start_date' => $cycleStart->toDateString(),
            'cycle_end_date' => $cycleEnd->toDateString(),
            'cycle_days' => $cycleStart->diffInDays($cycleEnd) + 1,
        ];
    }

    public static function defaultDashboardMonthCycle(?Carbon $today = null): string
    {
        return ($today ?: now())->copy()->subMonthNoOverflow()->format('Y-m');
    }
}
