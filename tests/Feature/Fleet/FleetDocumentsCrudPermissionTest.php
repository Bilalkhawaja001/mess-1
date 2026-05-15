<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetVehicleDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetDocumentsCrudPermissionTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter, FleetTestData;

    public function test_view_permission_can_list_documents_but_cannot_create(): void
    {
        $vehicle = $this->makeFleetVehicle();
        FleetVehicleDocument::create(['vehicle_id' => $vehicle->id, 'document_type' => 'Insurance', 'document_no' => 'INS-1', 'expiry_date' => now()->addMonth()->toDateString(), 'status' => 'valid']);
        $user = $this->userWithFleetPermissions(['fleet.documents.view']);

        $this->actingAs($user)->get(route('admin.fleet.documents.index'))->assertOk()->assertSee('Insurance');
        $this->actingAs($user)->post(route('admin.fleet.documents.store'), ['vehicle_id' => $vehicle->id, 'document_type' => 'Insurance', 'document_no' => 'INS-2', 'expiry_date' => now()->addMonths(2)->toDateString()])->assertForbidden();
    }

    public function test_manage_permission_can_create_document_and_validation_fails_when_expiry_missing(): void
    {
        $vehicle = $this->makeFleetVehicle();
        $user = $this->userWithFleetPermissions(['fleet.documents.view', 'fleet.documents.manage']);

        $this->actingAs($user)->post(route('admin.fleet.documents.store'), ['vehicle_id' => $vehicle->id, 'document_type' => 'Token Tax', 'document_no' => 'TT-1', 'expiry_date' => now()->addYear()->toDateString()])->assertRedirect();
        $this->assertDatabaseHas('fleet_vehicle_documents', ['vehicle_id' => $vehicle->id, 'document_type' => 'Token Tax', 'document_no' => 'TT-1']);

        $this->actingAs($user)->from(route('admin.fleet.documents.create'))->post(route('admin.fleet.documents.store'), ['vehicle_id' => $vehicle->id, 'document_type' => 'Fitness'])->assertSessionHasErrors('expiry_date');
    }
}
