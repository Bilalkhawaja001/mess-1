<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetMaintenanceLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetMaintenanceLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter, FleetTestData;

    public function test_maintenance_lifecycle_updates_database_statuses(): void
    {
        $vehicle = $this->makeFleetVehicle();
        $maintenance = FleetMaintenanceLog::create(['vehicle_id' => $vehicle->id, 'maintenance_date' => now()->toDateString(), 'odometer_reading' => 2000, 'maintenance_type' => 'Oil Change', 'parts_cost' => 1000, 'labour_cost' => 500, 'total_cost' => 1500, 'status' => 'Pending']);
        $user = $this->userWithFleetPermissions(['fleet.maintenance.view', 'fleet.maintenance.manage', 'fleet.maintenance.approve']);

        $this->actingAs($user)->post(route('admin.fleet.maintenance.approve', $maintenance))->assertRedirect();
        $this->assertDatabaseHas('fleet_maintenance_logs', ['id' => $maintenance->id, 'status' => 'Approved']);

        $this->actingAs($user)->post(route('admin.fleet.maintenance.start', $maintenance->refresh()))->assertRedirect();
        $this->assertDatabaseHas('fleet_maintenance_logs', ['id' => $maintenance->id, 'status' => 'In Progress']);

        $this->actingAs($user)->post(route('admin.fleet.maintenance.complete', $maintenance->refresh()), [
            'vehicle_id' => $vehicle->id,
            'maintenance_date' => now()->toDateString(),
            'odometer_reading' => 2200,
            'maintenance_type' => 'Oil Change',
            'parts_cost' => 1000,
            'labour_cost' => 500,
            'next_service_odometer' => 3000,
            'next_service_date' => now()->addMonths(3)->toDateString(),
            'status' => 'Completed',
            'invoice_no' => 'INV-100',
        ])->assertRedirect();
        $this->assertDatabaseHas('fleet_maintenance_logs', ['id' => $maintenance->id, 'status' => 'Completed']);

        $second = FleetMaintenanceLog::create(['vehicle_id' => $vehicle->id, 'maintenance_date' => now()->toDateString(), 'odometer_reading' => 2100, 'maintenance_type' => 'Brake Service', 'status' => 'Pending']);
        $this->actingAs($user)->post(route('admin.fleet.maintenance.cancel', $second))->assertRedirect();
        $this->assertDatabaseHas('fleet_maintenance_logs', ['id' => $second->id, 'status' => 'Cancelled']);
    }

    public function test_user_without_manage_permission_cannot_start_maintenance(): void
    {
        $vehicle = $this->makeFleetVehicle();
        $maintenance = FleetMaintenanceLog::create(['vehicle_id' => $vehicle->id, 'maintenance_date' => now()->toDateString(), 'odometer_reading' => 2000, 'maintenance_type' => 'Oil Change', 'status' => 'Approved']);
        $user = $this->userWithFleetPermissions(['fleet.maintenance.view']);

        $this->actingAs($user)->post(route('admin.fleet.maintenance.start', $maintenance))->assertForbidden();
    }
}
