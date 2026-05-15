<?php

namespace App\Services\Fleet;

use App\Models\Fleet\FleetChallan;
use App\Models\Fleet\FleetExpense;
use App\Models\Fleet\FleetFuelLog;
use App\Models\Fleet\FleetIncident;
use App\Models\Fleet\FleetMaintenanceLog;
use App\Models\Fleet\FleetTripLog;
use App\Models\Fleet\FleetTyreBattery;
use App\Models\Fleet\FleetVehicleDocument;
use Illuminate\Support\Facades\DB;

class FleetReportService
{
    public function summary(array $filters = []): array
    {
        return [
            'fuel_total' => (clone $this->fuelQuery($filters))->sum('total_amount'),
            'maintenance_total' => (clone $this->maintenanceQuery($filters))->sum('total_cost'),
            'expense_total' => (clone $this->expenseQuery($filters))->sum('amount'),
            'distance_total' => (clone $this->fuelQuery($filters))->sum('distance'),
            'incident_cost_total' => (clone $this->incidentQuery($filters))->sum('settled_cost'),
            'challan_total' => (clone $this->challanQuery($filters))->sum('amount'),
        ];
    }

    public function catalog(array $filters = []): array
    {
        return [
            'fuel_report' => $this->fuelReport($filters)['rows'],
            'vehicle_fuel_average' => $this->vehicleWiseFuelAverage($filters),
            'driver_fuel_average' => $this->driverWiseFuelAverage($filters),
            'vehicle_monthly_cost' => $this->vehicleMonthlyCost($filters),
            'maintenance_cost_by_vehicle' => $this->maintenanceCostByVehicle($filters),
            'document_expiry' => $this->documentExpiryReport($filters),
            'trip_utilization' => $this->tripUtilizationReport($filters),
            'tyre_battery_lifecycle' => $this->tyreBatteryLifecycleReport($filters),
            'challan_fines' => $this->challanFineReport($filters),
            'incident_cost' => $this->incidentCostReport($filters),
        ];
    }

    public function exportColumns(): array
    {
        return [
            'fuel_report' => ['fuel_date', 'vehicle_no', 'driver_name', 'liters', 'total_amount', 'distance', 'average_km_per_liter'],
            'vehicle_fuel_average' => ['vehicle_no', 'fuel_logs', 'liters', 'distance', 'fuel_cost', 'avg_km_per_liter'],
            'driver_fuel_average' => ['driver_name', 'fuel_logs', 'liters', 'distance', 'fuel_cost', 'avg_km_per_liter'],
            'vehicle_monthly_cost' => ['month', 'vehicle_no', 'fuel_cost', 'maintenance_cost', 'other_expense', 'total_cost'],
            'maintenance_cost_by_vehicle' => ['vehicle_no', 'maintenance_count', 'parts_cost', 'labour_cost', 'total_cost'],
            'document_expiry' => ['vehicle_no', 'document_type', 'document_no', 'expiry_date', 'status'],
            'trip_utilization' => ['vehicle_no', 'driver_name', 'trips', 'distance', 'first_trip_date', 'last_trip_date'],
            'tyre_battery_lifecycle' => ['vehicle_no', 'item_type', 'serial_no', 'installed_at', 'removed_at', 'life_km', 'cost', 'status'],
            'challan_fines' => ['vehicle_no', 'driver_name', 'challan_no', 'challan_date', 'violation_type', 'amount', 'status'],
            'incident_cost' => ['vehicle_no', 'driver_name', 'incident_date', 'incident_type', 'severity', 'estimated_cost', 'settled_cost', 'status'],
        ];
    }

    public function filters(): array
    {
        return [
            'from' => 'date: lower bound for log/report date',
            'to' => 'date: upper bound for log/report date',
            'vehicle_id' => 'integer: fleet_vehicles.id',
            'driver_id' => 'integer: fleet_drivers.id',
            'status' => 'string: module-specific status',
            'category' => 'string: expense category',
            'document_status' => 'string: valid, expiring_soon, expired',
        ];
    }

    public function vehicleWiseFuelAverage(array $filters = [])
    {
        return $this->fuelQuery($filters)
            ->selectRaw('fleet_fuel_logs.vehicle_id, fleet_vehicles.vehicle_no, COUNT(*) as fuel_logs, SUM(liters) as liters, SUM(distance) as distance, SUM(total_amount) as fuel_cost, CASE WHEN SUM(liters) > 0 THEN ROUND(SUM(distance) / SUM(liters), 2) ELSE 0 END as avg_km_per_liter')
            ->join('fleet_vehicles', 'fleet_vehicles.id', '=', 'fleet_fuel_logs.vehicle_id')
            ->groupBy('fleet_fuel_logs.vehicle_id', 'fleet_vehicles.vehicle_no')
            ->orderBy('fleet_vehicles.vehicle_no')
            ->get();
    }

    public function driverWiseFuelAverage(array $filters = [])
    {
        return $this->fuelQuery($filters)
            ->selectRaw('fleet_fuel_logs.driver_id, COALESCE(fleet_drivers.name, "Unassigned") as driver_name, COUNT(*) as fuel_logs, SUM(liters) as liters, SUM(distance) as distance, SUM(total_amount) as fuel_cost, CASE WHEN SUM(liters) > 0 THEN ROUND(SUM(distance) / SUM(liters), 2) ELSE 0 END as avg_km_per_liter')
            ->leftJoin('fleet_drivers', 'fleet_drivers.id', '=', 'fleet_fuel_logs.driver_id')
            ->groupBy('fleet_fuel_logs.driver_id', 'fleet_drivers.name')
            ->orderBy('driver_name')
            ->get();
    }

    public function vehicleMonthlyCost(array $filters = [])
    {
        $fuelMonth = $this->monthExpression('fuel_date');
        $maintenanceMonth = $this->monthExpression('maintenance_date');
        $expenseMonth = $this->monthExpression('expense_date');

        $fuel = $this->fuelQuery($filters)
            ->selectRaw("vehicle_id, {$fuelMonth} as month, SUM(total_amount) as fuel_cost, 0 as maintenance_cost, 0 as other_expense")
            ->groupBy('vehicle_id', 'month');

        $maintenance = $this->maintenanceQuery($filters)
            ->selectRaw("vehicle_id, {$maintenanceMonth} as month, 0 as fuel_cost, SUM(total_cost) as maintenance_cost, 0 as other_expense")
            ->groupBy('vehicle_id', 'month');

        $expense = $this->expenseQuery($filters)
            ->selectRaw("vehicle_id, {$expenseMonth} as month, 0 as fuel_cost, 0 as maintenance_cost, SUM(amount) as other_expense")
            ->whereNotNull('vehicle_id')
            ->groupBy('vehicle_id', 'month');

        return DB::query()
            ->fromSub($fuel->unionAll($maintenance)->unionAll($expense), 'costs')
            ->join('fleet_vehicles', 'fleet_vehicles.id', '=', 'costs.vehicle_id')
            ->selectRaw('costs.month, fleet_vehicles.vehicle_no, SUM(costs.fuel_cost) as fuel_cost, SUM(costs.maintenance_cost) as maintenance_cost, SUM(costs.other_expense) as other_expense, SUM(costs.fuel_cost + costs.maintenance_cost + costs.other_expense) as total_cost')
            ->groupBy('costs.month', 'fleet_vehicles.vehicle_no')
            ->orderBy('costs.month', 'desc')
            ->orderBy('fleet_vehicles.vehicle_no')
            ->get();
    }

    public function maintenanceCostByVehicle(array $filters = [])
    {
        return $this->maintenanceQuery($filters)
            ->selectRaw('fleet_maintenance_logs.vehicle_id, fleet_vehicles.vehicle_no, COUNT(*) as maintenance_count, SUM(parts_cost) as parts_cost, SUM(labour_cost) as labour_cost, SUM(total_cost) as total_cost')
            ->join('fleet_vehicles', 'fleet_vehicles.id', '=', 'fleet_maintenance_logs.vehicle_id')
            ->groupBy('fleet_maintenance_logs.vehicle_id', 'fleet_vehicles.vehicle_no')
            ->orderByDesc('total_cost')
            ->get();
    }

    public function documentExpiryReport(array $filters = [])
    {
        return $this->documentQuery($filters)
            ->join('fleet_vehicles', 'fleet_vehicles.id', '=', 'fleet_vehicle_documents.vehicle_id')
            ->select('fleet_vehicles.vehicle_no', 'fleet_vehicle_documents.document_type', 'fleet_vehicle_documents.document_no', 'fleet_vehicle_documents.expiry_date', 'fleet_vehicle_documents.status')
            ->orderBy('fleet_vehicle_documents.expiry_date')
            ->get();
    }

    public function tripUtilizationReport(array $filters = [])
    {
        return $this->tripQuery($filters)
            ->join('fleet_vehicles', 'fleet_vehicles.id', '=', 'fleet_trip_logs.vehicle_id')
            ->join('fleet_drivers', 'fleet_drivers.id', '=', 'fleet_trip_logs.driver_id')
            ->selectRaw('fleet_vehicles.vehicle_no, fleet_drivers.name as driver_name, COUNT(*) as trips, SUM(distance) as distance, MIN(trip_date) as first_trip_date, MAX(trip_date) as last_trip_date')
            ->groupBy('fleet_vehicles.vehicle_no', 'fleet_drivers.name')
            ->orderByDesc('distance')
            ->get();
    }

    public function tyreBatteryLifecycleReport(array $filters = [])
    {
        return $this->tyreBatteryQuery($filters)
            ->join('fleet_vehicles', 'fleet_vehicles.id', '=', 'fleet_tyres_batteries.vehicle_id')
            ->selectRaw('fleet_vehicles.vehicle_no, fleet_tyres_batteries.item_type, fleet_tyres_batteries.serial_no, fleet_tyres_batteries.installed_at, fleet_tyres_batteries.removed_at, CASE WHEN removed_odometer IS NOT NULL AND installed_odometer IS NOT NULL THEN removed_odometer - installed_odometer ELSE NULL END as life_km, fleet_tyres_batteries.cost, fleet_tyres_batteries.status')
            ->orderBy('fleet_vehicles.vehicle_no')
            ->get();
    }

    public function challanFineReport(array $filters = [])
    {
        return $this->challanQuery($filters)
            ->join('fleet_vehicles', 'fleet_vehicles.id', '=', 'fleet_challans.vehicle_id')
            ->leftJoin('fleet_drivers', 'fleet_drivers.id', '=', 'fleet_challans.driver_id')
            ->select('fleet_vehicles.vehicle_no', DB::raw('COALESCE(fleet_drivers.name, "Unassigned") as driver_name'), 'fleet_challans.challan_no', 'fleet_challans.challan_date', 'fleet_challans.violation_type', 'fleet_challans.amount', 'fleet_challans.status')
            ->orderByDesc('fleet_challans.challan_date')
            ->get();
    }

    public function incidentCostReport(array $filters = [])
    {
        return $this->incidentQuery($filters)
            ->join('fleet_vehicles', 'fleet_vehicles.id', '=', 'fleet_incidents.vehicle_id')
            ->leftJoin('fleet_drivers', 'fleet_drivers.id', '=', 'fleet_incidents.driver_id')
            ->select('fleet_vehicles.vehicle_no', DB::raw('COALESCE(fleet_drivers.name, "Unassigned") as driver_name'), 'fleet_incidents.incident_date', 'fleet_incidents.incident_type', 'fleet_incidents.severity', 'fleet_incidents.estimated_cost', 'fleet_incidents.settled_cost', 'fleet_incidents.status')
            ->orderByDesc('fleet_incidents.incident_date')
            ->get();
    }

    public function fuel(array $filters = [])
    {
        return $this->fuelQuery($filters)->with(['vehicle', 'driver'])->paginate(50)->withQueryString();
    }

    public function fuelReport(array $filters = []): array
    {
        $rows = $this->fuelQuery($filters)
            ->with(['vehicle', 'driver'])
            ->orderBy('fuel_date')
            ->orderBy('id')
            ->get();

        $totalsQuery = $this->fuelQuery($filters);
        $liters = (float) (clone $totalsQuery)->sum('liters');
        $distance = (float) (clone $totalsQuery)->sum('distance');
        $amount = (float) (clone $totalsQuery)->sum('total_amount');

        return [
            'rows' => $rows,
            'totals' => [
                'liters' => round($liters, 2),
                'amount' => round($amount, 2),
                'distance' => round($distance, 2),
                'average_km_per_liter' => $liters > 0 ? round($distance / $liters, 2) : 0.0,
            ],
        ];
    }

    public function fuelTotals(array $filters = []): array
    {
        $q = $this->fuelQuery($filters);
        return ['liters' => (clone $q)->sum('liters'), 'distance' => (clone $q)->sum('distance'), 'amount' => (clone $q)->sum('total_amount'), 'avg' => round((float) (clone $q)->avg('average_km_per_liter'), 2)];
    }

    public function maintenance(array $filters = [])
    {
        return $this->maintenanceQuery($filters)->with('vehicle')->paginate(50)->withQueryString();
    }

    public function maintenanceTotals(array $filters = []): array
    {
        $q = $this->maintenanceQuery($filters);
        return ['amount' => (clone $q)->sum('total_cost'), 'count' => (clone $q)->count()];
    }

    public function expenses(array $filters = [])
    {
        return $this->expenseQuery($filters)->with('vehicle')->paginate(50)->withQueryString();
    }

    public function expenseTotals(array $filters = []): array
    {
        $q = $this->expenseQuery($filters);
        return ['amount' => (clone $q)->sum('amount'), 'count' => (clone $q)->count()];
    }

    public function exportRows(string $report, array $filters = [])
    {
        return match ($report) {
            'fuel_report' => $this->fuelReport($filters)['rows'],
            'vehicle_fuel_average' => $this->vehicleWiseFuelAverage($filters),
            'driver_fuel_average' => $this->driverWiseFuelAverage($filters),
            'vehicle_monthly_cost' => $this->vehicleMonthlyCost($filters),
            'maintenance_cost_by_vehicle' => $this->maintenanceCostByVehicle($filters),
            'document_expiry' => $this->documentExpiryReport($filters),
            'trip_utilization' => $this->tripUtilizationReport($filters),
            'tyre_battery_lifecycle' => $this->tyreBatteryLifecycleReport($filters),
            'challan_fines' => $this->challanFineReport($filters),
            'incident_cost' => $this->incidentCostReport($filters),
            default => collect(),
        };
    }

    private function monthExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function fuelQuery(array $f)
    {
        return FleetFuelLog::query()
            ->when($f['vehicle_id'] ?? null, fn ($q, $v) => $q->where('fleet_fuel_logs.vehicle_id', $v))
            ->when($f['driver_id'] ?? null, fn ($q, $v) => $q->where('fleet_fuel_logs.driver_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('fuel_date', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('fuel_date', '<=', $v));
    }

    private function maintenanceQuery(array $f)
    {
        return FleetMaintenanceLog::query()
            ->when($f['vehicle_id'] ?? null, fn ($q, $v) => $q->where('fleet_maintenance_logs.vehicle_id', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('fleet_maintenance_logs.status', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('maintenance_date', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('maintenance_date', '<=', $v));
    }

    private function expenseQuery(array $f)
    {
        return FleetExpense::query()
            ->when($f['vehicle_id'] ?? null, fn ($q, $v) => $q->where('fleet_expenses.vehicle_id', $v))
            ->when($f['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('expense_date', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('expense_date', '<=', $v));
    }

    private function documentQuery(array $f)
    {
        return FleetVehicleDocument::query()
            ->when($f['vehicle_id'] ?? null, fn ($q, $v) => $q->where('fleet_vehicle_documents.vehicle_id', $v))
            ->when($f['document_status'] ?? null, fn ($q, $v) => $q->where('fleet_vehicle_documents.status', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('expiry_date', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('expiry_date', '<=', $v));
    }

    private function tripQuery(array $f)
    {
        return FleetTripLog::query()
            ->when($f['vehicle_id'] ?? null, fn ($q, $v) => $q->where('fleet_trip_logs.vehicle_id', $v))
            ->when($f['driver_id'] ?? null, fn ($q, $v) => $q->where('fleet_trip_logs.driver_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('trip_date', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('trip_date', '<=', $v));
    }

    private function tyreBatteryQuery(array $f)
    {
        return FleetTyreBattery::query()
            ->when($f['vehicle_id'] ?? null, fn ($q, $v) => $q->where('fleet_tyres_batteries.vehicle_id', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('fleet_tyres_batteries.status', $v));
    }

    private function challanQuery(array $f)
    {
        return FleetChallan::query()
            ->when($f['vehicle_id'] ?? null, fn ($q, $v) => $q->where('fleet_challans.vehicle_id', $v))
            ->when($f['driver_id'] ?? null, fn ($q, $v) => $q->where('fleet_challans.driver_id', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('fleet_challans.status', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('challan_date', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('challan_date', '<=', $v));
    }

    private function incidentQuery(array $f)
    {
        return FleetIncident::query()
            ->when($f['vehicle_id'] ?? null, fn ($q, $v) => $q->where('fleet_incidents.vehicle_id', $v))
            ->when($f['driver_id'] ?? null, fn ($q, $v) => $q->where('fleet_incidents.driver_id', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('fleet_incidents.status', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('incident_date', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('incident_date', '<=', $v));
    }
}
