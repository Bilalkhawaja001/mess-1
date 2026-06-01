<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AppSettingsWebsiteProofTest extends TestCase
{
    public function test_required_routes_exist_with_expected_middleware(): void
    {
        $this->assertTrue(Route::has('api.app-settings'));
        $this->assertTrue(Route::has('admin.app-settings.edit'));
        $this->assertTrue(Route::has('admin.app-settings.update'));
        $this->assertTrue(Route::has('member.app.dashboard'));
        $this->assertTrue(Route::has('member.app.statement.index'));
        $this->assertTrue(Route::has('member.app.menu.index'));
        $this->assertTrue(Route::has('member.app.complaints.index'));
        $this->assertTrue(Route::has('member.app.notifications.index'));
        $this->assertTrue(Route::has('member.device-token.store'));

        $this->assertContains('permission:settings.dangerous', Route::getRoutes()->getByName('admin.app-settings.edit')->middleware());
        $this->assertContains('app_feature:statement', Route::getRoutes()->getByName('member.app.statement.index')->middleware());
        $this->assertContains('app_feature:menu', Route::getRoutes()->getByName('member.app.menu.index')->middleware());
        $this->assertContains('app_feature:complaint', Route::getRoutes()->getByName('member.app.complaints.index')->middleware());
        $this->assertContains('app_feature:notification', Route::getRoutes()->getByName('member.app.notifications.index')->middleware());
    }

    public function test_public_payload_uses_safe_default_keys_only(): void
    {
        $payload = AppSetting::mobileControlDefaults();

        $this->assertArrayHasKey('mobile_app_enabled', $payload);
        $this->assertArrayHasKey('features', $payload);
        $this->assertArrayHasKey('support', $payload);
        $this->assertArrayHasKey('android', $payload);
        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('secret', $payload);
        $this->assertArrayNotHasKey('token', $payload);
    }
}
