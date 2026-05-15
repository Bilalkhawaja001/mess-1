<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetDriver;
use App\Models\Fleet\FleetVehicle;

trait FleetTestData
{
    protected function makeFleetVehicle(array $overrides = []): FleetVehicle
    {
        return FleetVehicle::create(array_merge([
            'vehicle_no' => 'FLT-' . fake()->unique()->numberBetween(1000, 9999),
            'make' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2022,
            'current_odometer' => 1000,
            'fuel_type' => 'Diesel',
            'status' => 'Active',
        ], $overrides));
    }

    protected function makeFleetDriver(array $overrides = []): FleetDriver
    {
        return FleetDriver::create(array_merge([
            'name' => 'Driver ' . fake()->unique()->numberBetween(1000, 9999),
            'employee_code' => 'DRV-' . fake()->unique()->numberBetween(100, 999),
            'mobile_no' => '03000000000',
            'license_no' => 'LIC-' . fake()->unique()->numberBetween(1000, 9999),
            'license_expiry_date' => now()->addYear()->toDateString(),
            'status' => 'Active',
        ], $overrides));
    }
}
