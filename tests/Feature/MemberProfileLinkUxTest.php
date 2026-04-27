<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberProfileLinkUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    private function memberUser(string $username = 'member1'): User
    {
        $role = Role::query()->where('code', 'MEMBER')->firstOrFail();

        return User::query()->create([
            'username' => $username,
            'name' => 'Member User',
            'email' => $username.'@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_member_dashboard_shows_warning_when_profile_missing(): void
    {
        $user = $this->memberUser();

        $this->actingAs($user)
            ->get('/member/dashboard')
            ->assertOk()
            ->assertSee('Your member profile is not linked yet. Please contact admin.')
            ->assertDontSee('href="'.route('member.payments.index').'"', false);
    }

    public function test_member_payments_redirects_to_dashboard_with_warning_when_profile_missing(): void
    {
        $user = $this->memberUser('member2');

        $this->actingAs($user)
            ->get('/member/payments')
            ->assertRedirect('/member/dashboard');

        $this->followRedirects($this->actingAs($user)->get('/member/payments'))
            ->assertSee('Your member profile is not linked yet. Please contact admin.');
    }

    public function test_member_payments_works_when_members_user_id_link_exists(): void
    {
        $user = $this->memberUser('member3');

        Member::query()->create([
            'user_id' => $user->id,
            'member_code' => 'M-001',
            'name' => 'Member User',
            'join_date' => '2026-04-01',
            'is_active' => true,
            'portal_enabled' => true,
        ]);

        $this->actingAs($user)->get('/member/payments')->assertOk();
    }
}
