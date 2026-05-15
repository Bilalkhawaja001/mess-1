<?php

namespace App\Services\Fleet;

use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetExpense;
use App\Models\Fleet\FleetFuelLog;
use App\Models\Fleet\FleetMaintenanceLog;
use App\Models\Fleet\FleetVehicle;
use App\Models\Fleet\FleetVehicleDocument;
use Illuminate\Support\Facades\DB;

class FleetKpiService
{
    public function dashboard(): array
    {
        $monthStart = now()->startOfMonth();

        return [
            'total_vehicles' => FleetVehicle::count(),
            'active_vehicles' => FleetVehicle::where('status', 'Active')->count(),
            'under_maintenance' => FleetVehicle::where('status', 'Under Maintenance')->count(),
            'monthly_fuel_cost' => round((float) FleetFuelLog::whereDate('fuel_date', '>=', $monthStart)->sum('total_amount'), 2),
            'monthly_maintenance_cost' => round((float) FleetMaintenanceLog::whereDate('maintenance_date', '>=', $monthStart)->sum('total_cost'), 2),
            'average_km_liter' => round((float) FleetFuelLog::whereDate('fuel_date', '>=', $monthStart)->avg('average_km_per_liter'), 2),
            'cost_per_km' => $this->costPerKm($monthStart),
        ];
    }

    public function costPerKm($from): float
    {
        $expense = FleetExpense::whereDate('expense_date', '>=', $from)->sum('amount');
        $distance = FleetFuelLog::whereDate('fuel_date', '>=', $from)->sum('distance');
        return $distance > 0 ? round($expense / $distance, 2) : 0;
    }

    public function monthlyFuelTrend(): array
    {
        $monthExpr = $this->monthExpression('fuel_date');

        return FleetFuelLog::selectRaw("{$monthExpr} as month, SUM(total_amount) as amount, SUM(liters) as liters, SUM(distance) as distance")
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->get()
            ->map(fn ($row) => ['month' => $row->month, 'amount' => (float) $row->amount, 'liters' => (float) $row->liters, 'distance' => (float) $row->distance])
            ->values()
            ->toArray();
    }

    public function monthlyMaintenanceTrend(): array
    {
        $monthExpr = $this->monthExpression('maintenance_date');

        return FleetMaintenanceLog::selectRaw("{$monthExpr} as month, SUM(total_cost) as amount")
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->get()
            ->map(fn ($row) => ['month' => $row->month, 'amount' => (float) $row->amount])
            ->values()
            ->toArray();
    }

    public function vehicleStatusBreakdown(): array
    {
        return FleetVehicle::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row) => ['status' => $row->status, 'count' => (int) $row->total])
            ->values()
            ->toArray();
    }

    public function vehicleAvailabilityBoard(): array
    {
        $active = FleetVehicle::where('status', 'Active')->count();
        $maintenance = FleetVehicle::where('status', 'Under Maintenance')->count();
        $inactive = FleetVehicle::whereIn('status', ['Inactive', 'Sold'])->count();

        return [
            ['label' => 'Available', 'count' => $active],
            ['label' => 'Under Maintenance', 'count' => $maintenance],
            ['label' => 'Unavailable', 'count' => $inactive],
        ];
    }

    public function maintenanceDueBoard(): array
    {
        return FleetMaintenanceLog::with('vehicle')
            ->where(function ($query) {
                $query->whereDate('next_service_date', '<=', now()->addDays(30))->orWhere('is_overdue', true);
            })
            ->orderByRaw('CASE WHEN is_overdue = 1 THEN 0 ELSE 1 END')
            ->orderBy('next_service_date')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'vehicle' => $row->vehicle?->vehicle_no ?? 'N/A',
                'due' => optional($row->next_service_date)->format('Y-m-d') ?? 'By odometer',
                'odometer' => $row->next_service_odometer ?? $row->odometer_reading,
                'status' => $row->is_overdue ? 'Overdue' : $row->status,
            ])
            ->values()
            ->toArray();
    }

    public function documentExpiryPanel(): array
    {
        return FleetVehicleDocument::with('vehicle')
            ->whereIn('status', ['expired', 'expiring_soon'])
            ->orderBy('expiry_date')
            ->limit(10)
            ->get()
            ->map(fn ($doc) => [
                'vehicle' => $doc->vehicle?->vehicle_no ?? 'N/A',
                'document_type' => $doc->document_type,
                'expiry_date' => optional($doc->expiry_date)->format('Y-m-d'),
                'status' => $doc->status,
            ])
            ->values()
            ->toArray();
    }

    public function driverPerformanceTable(): array
    {
        return FleetDriver::query()
            ->leftJoin('fleet_fuel_logs', 'fleet_fuel_logs.driver_id', '=', 'fleet_drivers.id')
            ->selectRaw('fleet_drivers.name as driver, COALESCE(SUM(fleet_fuel_logs.distance), 0) as km, CASE WHEN SUM(fleet_fuel_logs.liters) > 0 THEN ROUND(SUM(fleet_fuel_logs.distance) / SUM(fleet_fuel_logs.liters), 2) ELSE 0 END as avg_km_liter, COALESCE(SUM(CASE WHEN fleet_fuel_logs.is_abnormal_average = 1 THEN 1 ELSE 0 END), 0) as abnormal')
            ->groupBy('fleet_drivers.id', 'fleet_drivers.name')
            ->orderByDesc('km')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['driver' => $row->driver, 'km' => (float) $row->km, 'avg' => (float) $row->avg_km_liter, 'abnormal' => (int) $row->abnormal])
            ->values()
            ->toArray();
    }

    private function monthExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    public function quickActions(): array
    {
        return [
            ['label' => 'Add Vehicle', 'route' => 'admin.fleet.vehicles.create'],
            ['label' => 'Add Fuel', 'route' => 'admin.fleet.fuel.create'],
            ['label' => 'Add Maintenance', 'route' => 'admin.fleet.maintenance.create'],
            ['label' => 'Upload Document', 'route' => 'admin.fleet.documents.create'],
        ];
    }

    public function reportActions(): array
    {
        return [
            ['label' => 'Reports Center', 'route' => 'admin.fleet.reports.index'],
            ['label' => 'Export Reports', 'route' => 'admin.fleet.reports.export'],
        ];
    }
}
