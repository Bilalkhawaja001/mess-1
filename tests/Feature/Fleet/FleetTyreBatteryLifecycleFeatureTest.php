<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetTyreBattery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetTyreBatteryLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter, FleetTestData;

    public function test_manage_permission_creates_and_updates_tyre_battery_lifecycle(): void
    {
        $vehicle = $this->makeFleetVehicle();
        $user = $this->userWithFleetPermissions(['fleet.tyres_batteries.view', 'fleet.tyres_batteries.manage']);

        $payload = ['vehicle_id' => $vehicle->id, 'item_type' => 'tyre', 'brand' => 'General', 'serial_no' => 'TY-100', 'installed_at' => '2026-01-01', 'installed_odometer' => 1000, 'cost' => 40000, 'status' => 'Active'];
        $this->actingAs($user)->post(route('admin.fleet.tyres-batteries.store'), $payload)->assertRedirect();
        $record = FleetTyreBattery::where('serial_no', 'TY-100')->firstOrFail();
        $this->assertDatabaseHas('fleet_tyres_batteries', ['id' => $record->id, 'item_type' => 'tyre', 'status' => 'Active']);

        $this->actingAs($user)->put(route('admin.fleet.tyres-batteries.update', $record), array_merge($payload, ['removed_at' => '2026-05-01', 'removed_odometer' => 5000, 'status' => 'Removed']))->assertRedirect();
        $this->assertDatabaseHas('fleet_tyres_batteries', ['id' => $record->id, 'status' => 'Removed', 'removed_odometer' => 5000]);
    }

    public function test_view_permission_cannot_store_and_validation_rejects_missing_item_type(): void
    {
        $vehicle = $this->makeFleetVehicle();
        $viewOnly = $this->userWithFleetPermissions(['fleet.tyres_batteries.view']);
        $manager = $this->userWithFleetPermissions(['fleet.tyres_batteries.view', 'fleet.tyres_batteries.manage']);

        $this->actingAs($viewOnly)->post(route('admin.fleet.tyres-batteries.store'), ['vehicle_id' => $vehicle->id, 'item_type' => 'battery', 'status' => 'Active'])->assertForbidden();
        $this->actingAs($manager)->from(route('admin.fleet.tyres-batteries.create'))->post(route('admin.fleet.tyres-batteries.store'), ['vehicle_id' => $vehicle->id, 'status' => 'Active'])->assertSessionHasErrors('item_type');
    }
}
