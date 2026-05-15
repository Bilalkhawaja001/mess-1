<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetChallan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetChallanCrudFeatureTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter, FleetTestData;

    public function test_manage_permission_creates_and_updates_challan(): void
    {
        $vehicle = $this->makeFleetVehicle();
        $driver = $this->makeFleetDriver();
        $user = $this->userWithFleetPermissions(['fleet.challans.view', 'fleet.challans.manage']);
        $payload = ['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'challan_no' => 'CH-200', 'violation_type' => 'Speed', 'challan_date' => '2026-05-11', 'amount' => 1000, 'status' => 'Unpaid'];

        $this->actingAs($user)->post(route('admin.fleet.challans.store'), $payload)->assertRedirect();
        $challan = FleetChallan::where('challan_no', 'CH-200')->firstOrFail();
        $this->assertDatabaseHas('fleet_challans', ['id' => $challan->id, 'amount' => 1000, 'status' => 'Unpaid']);

        $this->actingAs($user)->put(route('admin.fleet.challans.update', $challan), array_merge($payload, ['status' => 'Paid']))->assertRedirect();
        $this->assertDatabaseHas('fleet_challans', ['id' => $challan->id, 'status' => 'Paid']);
    }

    public function test_view_permission_cannot_store_and_validation_requires_date(): void
    {
        $vehicle = $this->makeFleetVehicle();
        $viewOnly = $this->userWithFleetPermissions(['fleet.challans.view']);
        $manager = $this->userWithFleetPermissions(['fleet.challans.view', 'fleet.challans.manage']);

        $this->actingAs($viewOnly)->post(route('admin.fleet.challans.store'), ['vehicle_id' => $vehicle->id, 'amount' => 1000, 'status' => 'Unpaid'])->assertForbidden();
        $this->actingAs($manager)->from(route('admin.fleet.challans.create'))->post(route('admin.fleet.challans.store'), ['vehicle_id' => $vehicle->id, 'amount' => 1000, 'status' => 'Unpaid'])->assertSessionHasErrors('challan_date');
    }
}
