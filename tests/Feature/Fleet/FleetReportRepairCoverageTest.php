<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetChallan;
use App\Models\Fleet\FleetExpense;
use App\Models\Fleet\FleetFuelLog;
use App\Models\Fleet\FleetIncident;
use App\Models\Fleet\FleetMaintenanceLog;
use App\Models\Fleet\FleetTripLog;
use App\Models\Fleet\FleetTyreBattery;
use App\Models\Fleet\FleetVehicleDocument;
use App\Services\Fleet\FleetReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FleetReportRepairCoverageTest extends TestCase
{
    use RefreshDatabase, FleetTestData;

    public function test_monthly_cost_report_is_sqlite_portable_and_uses_real_cost_sources(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        $vehicle = $this->makeFleetVehicle(['vehicle_no' => 'FLT-SQLITE-1']);
        $driver = $this->makeFleetDriver();

        FleetFuelLog::create(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'fuel_date' => '2026-05-05', 'fuel_type' => 'Diesel', 'liters' => 40, 'rate_per_liter' => 300, 'total_amount' => 12000, 'previous_odometer' => 1000, 'odometer_reading' => 1400, 'distance' => 400, 'average_km_per_liter' => 10]);
        FleetMaintenanceLog::create(['vehicle_id' => $vehicle->id, 'maintenance_date' => '2026-05-07', 'odometer_reading' => 1450, 'maintenance_type' => 'Oil', 'parts_cost' => 2000, 'labour_cost' => 500, 'total_cost' => 2500, 'status' => 'Completed']);
        FleetExpense::create(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'expense_date' => '2026-05-08', 'category' => 'Parking', 'amount' => 300, 'description' => 'Parking']);

        $rows = app(FleetReportService::class)->vehicleMonthlyCost(['from' => '2026-05-01', 'to' => '2026-05-31', 'vehicle_id' => $vehicle->id]);

        $this->assertCount(1, $rows);
        $this->assertSame('2026-05', $rows->first()->month);
        $this->assertSame(14800.0, (float) $rows->first()->total_cost);
    }

    public function test_document_trip_tyre_challan_and_incident_reports_have_filtered_rows(): void
    {
        $vehicle = $this->makeFleetVehicle(['vehicle_no' => 'FLT-CATALOG-1']);
        $driver = $this->makeFleetDriver(['name' => 'Catalog Driver']);

        FleetVehicleDocument::create(['vehicle_id' => $vehicle->id, 'document_type' => 'Insurance', 'document_no' => 'INS-1', 'expiry_date' => '2026-05-15', 'status' => 'expiring_soon']);
        FleetTripLog::create(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'trip_date' => '2026-05-10', 'from_location' => 'A', 'to_location' => 'B', 'start_odometer' => 1000, 'end_odometer' => 1200, 'distance' => 200]);
        FleetTyreBattery::create(['vehicle_id' => $vehicle->id, 'item_type' => 'battery', 'serial_no' => 'BAT-1', 'installed_at' => '2026-01-01', 'installed_odometer' => 1000, 'removed_at' => '2026-05-01', 'removed_odometer' => 4000, 'cost' => 25000, 'status' => 'Removed']);
        FleetChallan::create(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'challan_no' => 'CH-1', 'challan_date' => '2026-05-12', 'violation_type' => 'Signal', 'amount' => 1000, 'status' => 'Unpaid']);
        FleetIncident::create(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'incident_date' => '2026-05-13', 'incident_type' => 'Scratch', 'severity' => 'minor', 'estimated_cost' => 5000, 'settled_cost' => 3000, 'status' => 'Settled']);

        $service = app(FleetReportService::class);
        $filters = ['from' => '2026-05-01', 'to' => '2026-05-31', 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id];

        $this->assertCount(1, $service->documentExpiryReport(['document_status' => 'expiring_soon', 'vehicle_id' => $vehicle->id]));
        $this->assertCount(1, $service->tripUtilizationReport($filters));
        $this->assertCount(1, $service->tyreBatteryLifecycleReport(['vehicle_id' => $vehicle->id, 'status' => 'Removed']));
        $this->assertCount(1, $service->challanFineReport($filters + ['status' => 'Unpaid']));
        $this->assertCount(1, $service->incidentCostReport($filters + ['status' => 'Settled']));
    }
}
