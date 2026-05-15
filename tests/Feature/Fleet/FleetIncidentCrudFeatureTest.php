<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetIncident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetIncidentCrudFeatureTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter, FleetTestData;

    public function test_manage_permission_creates_and_updates_incident(): void
    {
        $vehicle = $this->makeFleetVehicle();
        $driver = $this->makeFleetDriver();
        $user = $this->userWithFleetPermissions(['fleet.incidents.view', 'fleet.incidents.manage']);
        $payload = ['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'incident_date' => '2026-05-10', 'incident_type' => 'Accident', 'severity' => 'minor', 'location' => 'Gate', 'estimated_cost' => 5000, 'settled_cost' => 0, 'status' => 'Open'];

        $this->actingAs($user)->post(route('admin.fleet.incidents.store'), $payload)->assertRedirect();
        $incident = FleetIncident::where('incident_type', 'Accident')->firstOrFail();
        $this->assertDatabaseHas('fleet_incidents', ['id' => $incident->id, 'severity' => 'minor', 'status' => 'Open']);

        $this->actingAs($user)->put(route('admin.fleet.incidents.update', $incident), array_merge($payload, ['settled_cost' => 3000, 'status' => 'Settled']))->assertRedirect();
        $this->assertDatabaseHas('fleet_incidents', ['id' => $incident->id, 'settled_cost' => 3000, 'status' => 'Settled']);
    }

    public function test_view_permission_cannot_store_and_validation_requires_incident_date(): void
    {
        $vehicle = $this->makeFleetVehicle();
        $viewOnly = $this->userWithFleetPermissions(['fleet.incidents.view']);
        $manager = $this->userWithFleetPermissions(['fleet.incidents.view', 'fleet.incidents.manage']);

        $this->actingAs($viewOnly)->post(route('admin.fleet.incidents.store'), ['vehicle_id' => $vehicle->id, 'incident_type' => 'Accident', 'severity' => 'minor', 'status' => 'Open'])->assertForbidden();
        $this->actingAs($manager)->from(route('admin.fleet.incidents.create'))->post(route('admin.fleet.incidents.store'), ['vehicle_id' => $vehicle->id, 'incident_type' => 'Accident', 'severity' => 'minor', 'status' => 'Open'])->assertSessionHasErrors('incident_date');
    }
}
