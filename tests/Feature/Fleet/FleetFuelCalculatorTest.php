<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetVehicle;
use App\Services\Fleet\FleetFuelCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetFuelCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_distance_average_and_total(): void
    {
        $vehicle = FleetVehicle::create(['vehicle_no'=>'T-1','current_odometer'=>1000,'fuel_type'=>'Diesel','status'=>'Active']);
        $data = app(FleetFuelCalculator::class)->prepareFuelLog([
            'vehicle_id'=>$vehicle->id,
            'fuel_date'=>now()->toDateString(),
            'fuel_type'=>'Diesel',
            'liters'=>20,
            'rate_per_liter'=>250,
            'odometer_reading'=>1200,
        ]);
        $this->assertSame(200, (int)$data['distance']);
        $this->assertSame(10.0, (float)$data['average_km_per_liter']);
        $this->assertSame(5000.0, (float)$data['total_amount']);
    }
}
