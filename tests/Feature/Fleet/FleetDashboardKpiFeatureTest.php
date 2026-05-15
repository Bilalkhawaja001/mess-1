<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetExpense;
use App\Models\Fleet\FleetFuelLog;
use App\Models\Fleet\FleetMaintenanceLog;
use App\Services\Fleet\FleetKpiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetDashboardKpiFeatureTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter, FleetTestData;

    public function test_dashboard_kpis_are_calculated_from_seeded_records(): void
    {
        $vehicle = $this->makeFleetVehicle(['status' => 'Active']);
        $driver = $this->makeFleetDriver();
        FleetFuelLog::create(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'fuel_date' => now()->toDateString(), 'fuel_type' => 'Diesel', 'liters' => 25, 'rate_per_liter' => 300, 'total_amount' => 7500, 'previous_odometer' => 1000, 'odometer_reading' => 1250, 'distance' => 250, 'average_km_per_liter' => 10]);
        FleetExpense::create(['vehicle_id' => $vehicle->id, 'expense_date' => now()->toDateString(), 'category' => 'Fuel', 'amount' => 7500]);
        FleetMaintenanceLog::create(['vehicle_id' => $vehicle->id, 'maintenance_date' => now()->toDateString(), 'maintenance_type' => 'Oil', 'total_cost' => 2500, 'status' => 'Completed']);

        $kpis = app(FleetKpiService::class)->dashboard();

        $this->assertSame(1, $kpis['total_vehicles']);
        $this->assertSame(1, $kpis['active_vehicles']);
        $this->assertSame(7500.0, (float) $kpis['monthly_fuel_cost']);
        $this->assertSame(2500.0, (float) $kpis['monthly_maintenance_cost']);
        $this->assertSame(10.0, (float) $kpis['average_km_liter']);
        $this->assertSame(30.0, (float) $kpis['cost_per_km']);
    }

    public function test_dashboard_page_shows_boards_for_authorized_user(): void
    {
        $user = $this->userWithFleetPermissions(['fleet.dashboard.view']);
        $this->actingAs($user)->get(route('admin.fleet.dashboard'))->assertOk()->assertSee('Fleet Command Center')->assertSee('Fleet Status Board')->assertSee('Driver Performance');
    }
}
