<?php

namespace App\Services\Fleet;

use Carbon\Carbon;

class FleetMaintenanceScheduler
{
    public function prepareMaintenance(array $data, $existing = null): array
    {
        $parts = (float)($data['parts_cost'] ?? 0);
        $labour = (float)($data['labour_cost'] ?? 0);

        $data['total_cost'] = round($parts + $labour, 2);

        if (!empty($data['started_at']) && !empty($data['completed_at'])) {
            $data['downtime_minutes'] = Carbon::parse($data['started_at'])->diffInMinutes(Carbon::parse($data['completed_at']), false);
            if ($data['downtime_minutes'] < 0) {
                $data['downtime_minutes'] = 0;
            }
        }

        $data['is_overdue'] = $this->isOverdue($data);

        return $data;
    }

    public function isOverdue(array $data): bool
    {
        $byDate = !empty($data['next_service_date']) && Carbon::parse($data['next_service_date'])->isPast();
        $byOdo = isset($data['current_vehicle_odometer'], $data['next_service_odometer'])
            && (float)$data['current_vehicle_odometer'] >= (float)$data['next_service_odometer'];
        return $byDate || $byOdo;
    }

    public function dueStatus(?string $nextDate, ?float $nextOdometer, ?float $currentOdometer): string
    {
        if ($nextDate && Carbon::parse($nextDate)->isPast()) return 'overdue';
        if ($nextOdometer !== null && $currentOdometer !== null && $currentOdometer >= $nextOdometer) return 'overdue';
        if ($nextDate && Carbon::parse($nextDate)->lte(now()->addDays(15))) return 'due_soon';
        if ($nextOdometer !== null && $currentOdometer !== null && ($nextOdometer - $currentOdometer) <= 500) return 'due_soon';
        return 'ok';
    }
}
