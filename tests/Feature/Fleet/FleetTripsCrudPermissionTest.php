<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetTripLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetTripsCrudPermissionTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter, FleetTestData;

    public function test_view_permission_can_list_trips_but_cannot_store(): void
    {
        $vehicle = $this->makeFleetVehicle();
        $driver = $this->makeFleetDriver();
        FleetTripLog::create(['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'trip_date' => now()->toDateString(), 'from_location' => 'Mill', 'to_location' => 'City', 'start_odometer' => 1000, 'end_odometer' => 1120, 'distance' => 120]);
        $user = $this->userWithFleetPermissions(['fleet.trips.view']);

        $this->actingAs($user)->get(route('admin.fleet.trips.index'))->assertOk()->assertSee('City');
        $this->actingAs($user)->post(route('admin.fleet.trips.store'), ['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'trip_date' => now()->toDateString(), 'from_location' => 'A', 'to_location' => 'B', 'start_odometer' => 1200, 'end_odometer' => 1300])->assertForbidden();
    }

    public function test_manage_permission_can_create_trip_and_reject_invalid_odometer(): void
    {
        $vehicle = $this->makeFleetVehicle();
        $driver = $this->makeFleetDriver();
        $user = $this->userWithFleetPermissions(['fleet.trips.view', 'fleet.trips.manage']);

        $payload = ['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'trip_date' => now()->toDateString(), 'from_location' => 'Mill', 'to_location' => 'Port', 'start_odometer' => 1000, 'end_odometer' => 1100];
        $this->actingAs($user)->post(route('admin.fleet.trips.store'), $payload)->assertRedirect();
        $this->assertDatabaseHas('fleet_trip_logs', ['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'distance' => 100]);

        $this->actingAs($user)->from(route('admin.fleet.trips.create'))->post(route('admin.fleet.trips.store'), array_merge($payload, ['end_odometer' => 900]))->assertSessionHasErrors('end_odometer');
    }
}
