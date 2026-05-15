<?php

namespace Tests\Feature\Fleet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FleetSchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_required_fleet_schema_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('fleet_vehicles', ['vehicle_no', 'current_odometer', 'fuel_type', 'status']));
        $this->assertTrue(Schema::hasColumns('fleet_fuel_logs', ['vehicle_id', 'liters', 'odometer_reading', 'average_km_per_liter']));
        $this->assertTrue(Schema::hasColumns('fleet_maintenance_logs', ['vehicle_id', 'status', 'total_cost', 'next_service_date']));
        $this->assertTrue(Schema::hasColumns('fleet_vehicle_documents', ['vehicle_id', 'document_type', 'expiry_date', 'status']));
    }
}
