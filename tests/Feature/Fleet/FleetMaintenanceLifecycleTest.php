<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetMaintenanceLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetMaintenanceLifecycleTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter, FleetTestData;

    public function test_moves_maintenance_through_approval_to_completion_with_closure_proof(): void
    {
        $user = $this->userWithFleetPermissions(['fleet.maintenance.approve', 'fleet.maintenance.manage']);
        $vehicle = $this->makeFleetVehicle();
        $maintenance = FleetMaintenanceLog::query()->create([
            'vehicle_id' => $vehicle->id,
            'maintenance_type' => 'Preventive',
            'maintenance_date' => now()->toDateString(),
            'odometer_reading' => 1000,
            'description' => 'Oil change',
            'status' => 'Pending',
        ]);

        $this->actingAs($user)->post(route('admin.fleet.maintenance.approve', $maintenance))->assertRedirect();
        $this->assertSame('Approved', $maintenance->fresh()->status);

        $this->actingAs($user)->post(route('admin.fleet.maintenance.start', $maintenance))->assertRedirect();
        $this->assertSame('In Progress', $maintenance->fresh()->status);

        $this->actingAs($user)->post(route('admin.fleet.maintenance.complete', $maintenance), [
            'vehicle_id' => $vehicle->id,
            'maintenance_date' => now()->toDateString(),
            'odometer_reading' => 12345,
            'maintenance_type' => 'Preventive',
            'status' => 'Completed',
            'parts_cost' => 1000,
            'labour_cost' => 500,
        ])->assertRedirect();

        $this->assertSame('Completed', $maintenance->fresh()->status);
    }
}
