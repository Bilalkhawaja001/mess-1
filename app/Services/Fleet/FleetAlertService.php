<?php

namespace App\Services\Fleet;

use App\Models\Fleet\FleetFuelLog;
use App\Models\Fleet\FleetMaintenanceLog;
use App\Models\Fleet\FleetVehicleDocument;

class FleetAlertService
{
    public function dashboardAlerts(): array
    {
        $alerts = [];

        foreach (FleetVehicleDocument::with('vehicle')->where('status', 'expired')->limit(5)->get() as $doc) {
            $alerts[] = ['level' => 'danger', 'message' => ($doc->vehicle?->vehicle_no ?? 'Vehicle') . ' ' . $doc->document_type . ' expired on ' . optional($doc->expiry_date)->format('Y-m-d')];
        }

        foreach (FleetVehicleDocument::with('vehicle')->where('status', 'expiring_soon')->limit(5)->get() as $doc) {
            $alerts[] = ['level' => 'warning', 'message' => ($doc->vehicle?->vehicle_no ?? 'Vehicle') . ' ' . $doc->document_type . ' expires on ' . optional($doc->expiry_date)->format('Y-m-d')];
        }

        foreach (FleetMaintenanceLog::with('vehicle')->where('is_overdue', true)->limit(5)->get() as $log) {
            $alerts[] = ['level' => 'danger', 'message' => ($log->vehicle?->vehicle_no ?? 'Vehicle') . ' maintenance overdue'];
        }

        foreach (FleetFuelLog::with('vehicle')->where('is_abnormal_average', true)->latest('fuel_date')->limit(5)->get() as $fuel) {
            $alerts[] = ['level' => 'warning', 'message' => ($fuel->vehicle?->vehicle_no ?? 'Vehicle') . ' abnormal fuel average: ' . $fuel->average_km_per_liter . ' KM/L'];
        }

        return $alerts;
    }
}
