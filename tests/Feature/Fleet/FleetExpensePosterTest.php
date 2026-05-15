<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetFuelLog;
use App\Models\Fleet\FleetVehicle;
use App\Services\Fleet\FleetExpensePoster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetExpensePosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_fuel_expense_is_posted_once(): void
    {
        $vehicle = FleetVehicle::create(['vehicle_no'=>'T-1','status'=>'Active','fuel_type'=>'Diesel']);
        $log = FleetFuelLog::create(['vehicle_id'=>$vehicle->id,'fuel_date'=>now(),'fuel_type'=>'Diesel','liters'=>10,'rate_per_liter'=>250,'total_amount'=>2500,'odometer_reading'=>100,'previous_odometer'=>0,'distance'=>100,'average_km_per_liter'=>10]);
        app(FleetExpensePoster::class)->postFuelExpense($log);
        app(FleetExpensePoster::class)->postFuelExpense($log);
        $this->assertDatabaseCount('fleet_expenses', 1);
    }
}
