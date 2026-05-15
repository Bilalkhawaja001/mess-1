<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetChallan;
use App\Models\Fleet\FleetFuelLog;
use App\Models\Fleet\FleetIncident;
use App\Models\Fleet\FleetMaintenanceLog;
use App\Models\Fleet\FleetTripLog;
use App\Models\Fleet\FleetTyreBattery;
use App\Models\Fleet\FleetVehicleDocument;
use App\Services\Fleet\FleetReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetReportCatalogFeatureTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter, FleetTestData;

    public function test_report_filters_return_seeded_totals_and_export_columns(): void
    {
        $vehicle = $this->makeFleetVehicle(['vehicle_no' => 'FLT-RPT-1']);
        $driver = $this->makeFleetDriver(['name' => 'Report Driver']);
        FleetFuelLog::create(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'fuel_date' => '2026-05-01', 'fuel_type' => 'Diesel', 'liters' => 50, 'rate_per_liter' => 300, 'total_amount' => 15000, 'previous_odometer' => 1000, 'odometer_reading' => 1500, 'distance' => 500, 'average_km_per_liter' => 10]);
        FleetMaintenanceLog::create(['vehicle_id' => $vehicle->id, 'maintenance_date' => '2026-05-02', 'maintenance_type' => 'Oil', 'parts_cost' => 2000, 'labour_cost' => 500, 'total_cost' => 2500, 'status' => 'Completed']);
        FleetVehicleDocument::create(['vehicle_id' => $vehicle->id, 'document_type' => 'Insurance', 'document_no' => 'DOC-1', 'expiry_date' => '2026-05-30', 'status' => 'expiring_soon']);
        FleetTripLog::create(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'trip_date' => '2026-05-03', 'from_location' => 'A', 'to_location' => 'B', 'start_odometer' => 1500, 'end_odometer' => 1600, 'distance' => 100]);
        FleetTyreBattery::create(['vehicle_id' => $vehicle->id, 'item_type' => 'tyre', 'serial_no' => 'TY-1', 'installed_at' => '2026-01-01', 'installed_odometer' => 1000, 'removed_at' => '2026-05-01', 'removed_odometer' => 5000, 'cost' => 40000, 'status' => 'Removed']);
        FleetChallan::create(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'challan_no' => 'CH-1', 'challan_date' => '2026-05-04', 'violation_type' => 'Speed', 'amount' => 1000, 'status' => 'Unpaid']);
        FleetIncident::create(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'incident_date' => '2026-05-05', 'incident_type' => 'Accident', 'severity' => 'minor', 'estimated_cost' => 5000, 'settled_cost' => 3000, 'status' => 'Settled']);

        $service = app(FleetReportService::class);
        $filters = ['from' => '2026-05-01', 'to' => '2026-05-31', 'vehicle_id' => $vehicle->id];

        $this->assertSame(15000.0, (float) $service->summary($filters)['fuel_total']);
        $this->assertCount(1, $service->vehicleWiseFuelAverage($filters));
        $this->assertCount(1, $service->driverWiseFuelAverage($filters));
        $this->assertCount(1, $service->maintenanceCostByVehicle($filters));
        $this->assertCount(1, $service->documentExpiryReport(['document_status' => 'expiring_soon']));
        $this->assertCount(1, $service->tripUtilizationReport($filters));
        $this->assertCount(1, $service->tyreBatteryLifecycleReport($filters));
        $this->assertCount(1, $service->challanFineReport($filters));
        $this->assertCount(1, $service->incidentCostReport($filters));
        $this->assertContains('avg_km_per_liter', $service->exportColumns()['vehicle_fuel_average']);
    }

    public function test_reports_page_requires_view_permission(): void
    {
        $allowed = $this->userWithFleetPermissions(['fleet.reports.view']);
        $blocked = $this->userWithoutFleetPermissions();

        $this->actingAs($allowed)->get(route('admin.fleet.reports.index'))->assertOk();
        $this->actingAs($blocked)->get(route('admin.fleet.reports.index'))->assertForbidden();
    }
}
