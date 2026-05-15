<?php

namespace App\Services\Fleet;

use App\Models\Fleet\FleetExpense;
use App\Models\Fleet\FleetFuelLog;
use App\Models\Fleet\FleetMaintenanceLog;

class FleetExpensePoster
{
    public function postFuelExpense(FleetFuelLog $log): FleetExpense
    {
        return FleetExpense::updateOrCreate(
            ['source_type' => 'fuel', 'source_id' => $log->id],
            [
                'vehicle_id' => $log->vehicle_id,
                'driver_id' => $log->driver_id,
                'expense_date' => $log->fuel_date,
                'category' => 'Fuel',
                'amount' => $log->total_amount,
                'description' => 'Fuel log #' . $log->id,
            ]
        );
    }

    public function syncFuelExpense(FleetFuelLog $log): FleetExpense
    {
        return $this->postFuelExpense($log);
    }

    public function postMaintenanceExpense(FleetMaintenanceLog $log): FleetExpense
    {
        return FleetExpense::updateOrCreate(
            ['source_type' => 'maintenance', 'source_id' => $log->id],
            [
                'vehicle_id' => $log->vehicle_id,
                'expense_date' => $log->maintenance_date,
                'category' => 'Maintenance',
                'amount' => $log->total_cost,
                'description' => $log->maintenance_type . ' #' . $log->id,
            ]
        );
    }

    public function syncMaintenanceExpense(FleetMaintenanceLog $log): FleetExpense
    {
        return $this->postMaintenanceExpense($log);
    }

    public function removeSourceExpense(string $sourceType, int $sourceId): void
    {
        FleetExpense::where('source_type', $sourceType)->where('source_id', $sourceId)->delete();
    }
}
