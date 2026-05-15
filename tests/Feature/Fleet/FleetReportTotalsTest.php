<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetFuelLog;
use App\Services\Fleet\FleetReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetReportTotalsTest extends TestCase
{
    use RefreshDatabase, FleetTestData;

    public function test_fuel_report_returns_real_filtered_rows_and_totals(): void
    {
        $vehicleA = $this->makeFleetVehicle(['vehicle_no' => 'FLT-TOTAL-A']);
        $vehicleB = $this->makeFleetVehicle(['vehicle_no' => 'FLT-TOTAL-B']);
        $driverA = $this->makeFleetDriver(['name' => 'Totals Driver A']);
        $driverB = $this->makeFleetDriver(['name' => 'Totals Driver B']);

        FleetFuelLog::create(['vehicle_id' => $vehicleA->id, 'driver_id' => $driverA->id, 'fuel_date' => '2026-05-05', 'fuel_type' => 'Diesel', 'liters' => 40, 'rate_per_liter' => 300, 'total_amount' => 12000, 'previous_odometer' => 1000, 'odometer_reading' => 1400, 'distance' => 400, 'average_km_per_liter' => 10]);
        FleetFuelLog::create(['vehicle_id' => $vehicleA->id, 'driver_id' => $driverA->id, 'fuel_date' => '2026-05-20', 'fuel_type' => 'Diesel', 'liters' => 20, 'rate_per_liter' => 310, 'total_amount' => 6200, 'previous_odometer' => 1400, 'odometer_reading' => 1580, 'distance' => 180, 'average_km_per_liter' => 9]);
        FleetFuelLog::create(['vehicle_id' => $vehicleB->id, 'driver_id' => $driverB->id, 'fuel_date' => '2026-05-10', 'fuel_type' => 'Diesel', 'liters' => 50, 'rate_per_liter' => 300, 'total_amount' => 15000, 'previous_odometer' => 2000, 'odometer_reading' => 2500, 'distance' => 500, 'average_km_per_liter' => 10]);
        FleetFuelLog::create(['vehicle_id' => $vehicleA->id, 'driver_id' => $driverA->id, 'fuel_date' => '2026-06-01', 'fuel_type' => 'Diesel', 'liters' => 10, 'rate_per_liter' => 300, 'total_amount' => 3000, 'previous_odometer' => 1580, 'odometer_reading' => 1660, 'distance' => 80, 'average_km_per_liter' => 8]);

        $report = app(FleetReportService::class)->fuelReport([
            'from' => '2026-05-01',
            'to' => '2026-05-31',
            'vehicle_id' => $vehicleA->id,
            'driver_id' => $driverA->id,
        ]);

        $this->assertCount(2, $report['rows']);
        $this->assertSame(['rows', 'totals'], array_keys($report));
        $this->assertSame(60.0, $report['totals']['liters']);
        $this->assertSame(18200.0, $report['totals']['amount']);
        $this->assertSame(580.0, $report['totals']['distance']);
        $this->assertSame(9.67, $report['totals']['average_km_per_liter']);
    }
}
