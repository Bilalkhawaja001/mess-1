<?php

namespace Tests\Feature\Fleet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetPermissionsTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter;

    public function test_fleet_routes_require_authentication(): void
    {
        $this->get('/admin/fleet')->assertRedirect();
    }

    public function test_user_with_fleet_view_permission_can_access_dashboard(): void
    {
        $user = $this->userWithFleetPermissions(['fleet.dashboard.view']);

        $this->actingAs($user)->get('/admin/fleet')->assertOk();
    }

    public function test_user_without_fleet_permission_is_forbidden(): void
    {
        $user = $this->userWithoutFleetPermissions();

        $this->actingAs($user)->get('/admin/fleet')->assertForbidden();
    }
}
