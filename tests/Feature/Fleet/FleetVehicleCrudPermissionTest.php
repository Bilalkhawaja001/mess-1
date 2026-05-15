<?php

namespace Tests\Feature\Fleet;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class FleetVehicleCrudPermissionTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter;

    public function test_view_permission_can_open_index_but_cannot_create_vehicle(): void
    {
        $user = $this->userWithFleetPermissions(['fleet.vehicles.view']);
        $this->actingAs($user)->get('/admin/fleet/vehicles')->assertOk();
        $this->actingAs($user)->get('/admin/fleet/vehicles/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/fleet/vehicles', [])->assertForbidden();
    }

    public function test_manage_permission_can_create_vehicle(): void
    {
        $user = $this->userWithFleetPermissions(['fleet.vehicles.view','fleet.vehicles.manage']);
        $vehicleTypeId = DB::table('fleet_vehicle_types')->insertGetId([
            'name' => 'Truck',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $payload = [
            'vehicle_no' => 'FLT-001', 'vehicle_type_id' => $vehicleTypeId, 'fuel_type' => 'Diesel',
            'status' => 'Active', 'current_odometer' => 1000, 'registration_no' => 'REG-001'
        ];
        $this->actingAs($user)->post('/admin/fleet/vehicles', $payload)->assertRedirect();
        $this->assertDatabaseHas('fleet_vehicles', ['vehicle_no' => 'FLT-001']);
    }
}
