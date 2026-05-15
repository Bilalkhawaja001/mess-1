<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetExpense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetFuelFeatureTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter, FleetTestData;

    public function test_calculates_fuel_average_and_posts_expense(): void
    {
        $user = $this->userWithFleetPermissions(['fleet.fuel.manage']);
        $vehicle = $this->makeFleetVehicle(['current_odometer' => 1000]);

        $payload = [
            'vehicle_id' => $vehicle->id,
            'fuel_date' => now()->toDateString(),
            'fuel_type' => 'Diesel',
            'liters' => 50,
            'rate_per_liter' => 300,
            'odometer_reading' => 1250,
        ];

        $this->actingAs($user)->post(route('admin.fleet.fuel.store'), $payload)->assertRedirect();

        $this->assertSame(1250, $vehicle->fresh()->current_odometer);
        $this->assertTrue(FleetExpense::where('vehicle_id', $vehicle->id)->where('category', 'Fuel')->exists());
    }

    public function test_rejects_odometer_rollback_on_fuel_entry(): void
    {
        $user = $this->userWithFleetPermissions(['fleet.fuel.manage']);
        $vehicle = $this->makeFleetVehicle(['current_odometer' => 1000]);

        $payload = [
            'vehicle_id' => $vehicle->id,
            'fuel_date' => now()->toDateString(),
            'fuel_type' => 'Diesel',
            'liters' => 10,
            'rate_per_liter' => 300,
            'odometer_reading' => 900,
        ];

        $this->actingAs($user)->post(route('admin.fleet.fuel.store'), $payload)->assertSessionHasErrors('odometer_reading');
    }
}
