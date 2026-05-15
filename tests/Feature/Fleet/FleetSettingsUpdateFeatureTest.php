<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetSettingsUpdateFeatureTest extends TestCase
{
    use RefreshDatabase, FleetPermissionTestAdapter;

    public function test_manage_permission_updates_key_value_settings(): void
    {
        $user = $this->userWithFleetPermissions(['fleet.settings.view', 'fleet.settings.manage']);

        $payload = ['settings' => ['alert_days' => 45, 'fuel_average_min_km_per_liter' => 4, 'fuel_average_max_km_per_liter' => 14, 'maintenance_due_km_buffer' => 500, 'maintenance_due_days_buffer' => 15]];
        $this->actingAs($user)->post(route('admin.fleet.settings.update'), $payload)->assertRedirect();

        $this->assertDatabaseHas('fleet_settings', ['key' => 'alert_days', 'value' => '45']);
        $this->assertSame('14', FleetSetting::where('key', 'fuel_average_max_km_per_liter')->value('value'));
    }

    public function test_view_user_cannot_update_settings_and_validation_errors_are_returned(): void
    {
        $viewOnly = $this->userWithFleetPermissions(['fleet.settings.view']);
        $manager = $this->userWithFleetPermissions(['fleet.settings.view', 'fleet.settings.manage']);

        $this->actingAs($viewOnly)->post(route('admin.fleet.settings.update'), ['settings' => ['alert_days' => 30]])->assertForbidden();
        $this->actingAs($manager)->from(route('admin.fleet.settings.index'))->post(route('admin.fleet.settings.update'), ['settings' => ['alert_days' => 0]])->assertSessionHasErrors('settings.alert_days');
    }
}
