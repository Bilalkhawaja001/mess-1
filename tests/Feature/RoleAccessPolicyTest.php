<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    private function makeUser(string $roleCode): User
    {
        $role = Role::query()->where('code', $roleCode)->firstOrFail();

        return User::query()->create([
            'username' => strtolower($roleCode).'_user',
            'name' => $roleCode.' User',
            'email' => strtolower($roleCode).'@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_super_admin_can_access_users_settings_and_hard_reset(): void
    {
        $user = $this->makeUser('SUPER_ADMIN');

        $this->actingAs($user)->get('/admin/users')->assertOk();
        $this->actingAs($user)->get('/admin/settings')->assertOk();
        $this->actingAs($user)->post('/admin/month-governance/hard-reset', ['month_cycle' => '2026-04'])->assertStatus(302);
    }

    public function test_admin_can_access_users_but_cannot_access_settings_or_hard_reset(): void
    {
        $user = $this->makeUser('ADMIN');

        $this->actingAs($user)->get('/admin/users')->assertOk();
        $this->actingAs($user)->get('/admin/settings')->assertForbidden();
        $this->actingAs($user)->post('/admin/month-governance/hard-reset', ['month_cycle' => '2026-04'])->assertForbidden();
    }

    public function test_data_entry_cannot_access_users_settings_rates_or_month_governance(): void
    {
        $user = $this->makeUser('DATA_ENTRY');

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/settings')->assertForbidden();
        $this->actingAs($user)->get('/admin/rates')->assertForbidden();
        $this->actingAs($user)->get('/admin/month-governance')->assertForbidden();
    }

    public function test_auditor_cannot_access_users_settings_rates_or_post_operational_actions(): void
    {
        $user = $this->makeUser('AUDITOR');

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/settings')->assertForbidden();
        $this->actingAs($user)->get('/admin/rates')->assertForbidden();
        $this->actingAs($user)->post('/admin/users', [])->assertForbidden();
        $this->actingAs($user)->post('/admin/month-governance/close', ['month_cycle' => '2026-04'])->assertForbidden();
    }

    public function test_member_cannot_access_admin_routes_but_can_access_member_dashboard(): void
    {
        $user = $this->makeUser('MEMBER');

        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/member/dashboard')->assertOk();
    }

    public function test_permissions_are_seeded_as_approved(): void
    {
        $admin = Role::query()->where('code', 'ADMIN')->firstOrFail();
        $dataEntry = Role::query()->where('code', 'DATA_ENTRY')->firstOrFail();
        $auditor = Role::query()->where('code', 'AUDITOR')->firstOrFail();
        $member = Role::query()->where('code', 'MEMBER')->firstOrFail();

        $this->assertTrue($admin->permissions()->where('code', 'users.manage')->exists());
        $this->assertTrue($admin->permissions()->where('code', 'rates.manage')->exists());
        $this->assertFalse($admin->permissions()->where('code', 'month.reset_hard')->exists());
        $this->assertFalse($admin->permissions()->where('code', 'settings.dangerous')->exists());

        $this->assertFalse($dataEntry->permissions()->where('code', 'users.manage')->exists());
        $this->assertFalse($dataEntry->permissions()->where('code', 'rates.manage')->exists());
        $this->assertTrue($dataEntry->permissions()->where('code', 'menu.manage')->exists());

        $this->assertTrue($auditor->permissions()->where('code', 'report.export')->exists());
        $this->assertFalse($auditor->permissions()->where('code', 'menu.manage')->exists());

        $this->assertTrue($member->permissions()->where('code', 'complaint.submit_own')->exists());
        $this->assertTrue($member->permissions()->where('code', 'menu.view')->exists());

        $this->assertDatabaseHas('permissions', ['code' => 'settings.dangerous']);
        $this->assertDatabaseHas('permissions', ['code' => 'complaint.export']);
        $this->assertDatabaseHas('permissions', ['code' => 'menu.approve']);
    }
}
