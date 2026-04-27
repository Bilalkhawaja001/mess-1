<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintMenuAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    private function user(string $roleCode, string $suffix = 'a'): User
    {
        $role = Role::query()->where('code', $roleCode)->firstOrFail();

        return User::query()->create([
            'username' => strtolower($roleCode).$suffix,
            'name' => $roleCode.' '.$suffix,
            'email' => strtolower($roleCode).$suffix.'@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_member_can_submit_own_complaint(): void
    {
        $member = $this->user('MEMBER');

        $this->actingAs($member)->post('/member/complaints', [
            'type' => 'COMPLAINT',
            'subject' => 'Food issue',
            'description' => 'Rice was cold',
            'priority' => 'NORMAL',
        ])->assertRedirect('/member/complaints');

        $this->assertDatabaseHas('complaints', [
            'user_id' => $member->id,
            'subject' => 'Food issue',
        ]);
    }

    public function test_member_cannot_view_other_member_complaint(): void
    {
        $memberA = $this->user('MEMBER', 'a');
        $memberB = $this->user('MEMBER', 'b');

        $complaint = Complaint::query()->create([
            'complaint_no' => 'CMP-1',
            'user_id' => $memberA->id,
            'submitted_by_name' => $memberA->name,
            'type' => 'COMPLAINT',
            'subject' => 'Private',
            'description' => 'Private complaint',
            'priority' => 'NORMAL',
            'status' => 'OPEN',
        ]);

        $this->actingAs($memberB)->get('/member/complaints/'.$complaint->id)->assertForbidden();
    }

    public function test_member_can_view_menu(): void
    {
        $member = $this->user('MEMBER');
        Menu::query()->create([
            'title' => 'Lunch Menu',
            'menu_date' => '2026-04-27',
            'meal_type' => 'LUNCH',
            'items_text' => 'Rice\nChicken',
            'status' => 'APPROVED',
        ]);

        $this->actingAs($member)->get('/member/menu')->assertOk()->assertSee('Lunch Menu');
    }

    public function test_data_entry_can_manage_complaint_but_cannot_edit_or_approve_menu_or_close_complaint(): void
    {
        $user = $this->user('DATA_ENTRY');
        $complaint = Complaint::query()->create([
            'complaint_no' => 'CMP-2',
            'type' => 'COMPLAINT',
            'subject' => 'Issue',
            'description' => 'Issue body',
            'priority' => 'NORMAL',
            'status' => 'OPEN',
        ]);
        $menu = Menu::query()->create([
            'title' => 'Draft Menu',
            'menu_date' => '2026-04-27',
            'meal_type' => 'DINNER',
            'items_text' => 'Dal',
            'status' => 'DRAFT',
        ]);

        $this->actingAs($user)->post('/admin/complaints/'.$complaint->id.'/status', [
            'status' => 'IN_PROGRESS',
        ])->assertRedirect();

        $this->actingAs($user)->post('/admin/complaints/'.$complaint->id.'/status', [
            'status' => 'CLOSED',
        ])->assertForbidden();

        $this->actingAs($user)->put('/admin/menu/'.$menu->id, [
            'menu_date' => '2026-04-27',
            'meal_type' => 'DINNER',
            'title' => 'Draft Menu Updated',
            'description' => '',
            'items_text' => 'Dal Fry',
            'remarks' => '',
        ])->assertForbidden();

        $this->actingAs($user)->post('/admin/menu/'.$menu->id.'/approve')->assertForbidden();
        $this->actingAs($user)->post('/admin/menu/'.$menu->id.'/inactive')->assertForbidden();
    }

    public function test_auditor_can_view_export_but_cannot_post(): void
    {
        $user = $this->user('AUDITOR');
        $complaint = Complaint::query()->create([
            'complaint_no' => 'CMP-3',
            'type' => 'SUGGESTION',
            'subject' => 'Suggest',
            'description' => 'Suggestion',
            'priority' => 'LOW',
            'status' => 'OPEN',
        ]);

        $this->actingAs($user)->get('/admin/complaints')->assertOk();
        $this->actingAs($user)->get('/admin/complaints/export')->assertOk();
        $this->actingAs($user)->post('/admin/complaints/'.$complaint->id.'/status', ['status' => 'IN_PROGRESS'])->assertForbidden();
    }

    public function test_admin_can_close_complaint_and_approve_menu(): void
    {
        $user = $this->user('ADMIN');
        $complaint = Complaint::query()->create([
            'complaint_no' => 'CMP-4',
            'type' => 'COMPLAINT',
            'subject' => 'Close me',
            'description' => 'Close body',
            'priority' => 'HIGH',
            'status' => 'OPEN',
        ]);
        $menu = Menu::query()->create([
            'title' => 'Admin Menu',
            'menu_date' => '2026-04-27',
            'meal_type' => 'LUNCH',
            'items_text' => 'Rice',
            'status' => 'DRAFT',
        ]);

        $this->actingAs($user)->post('/admin/complaints/'.$complaint->id.'/status', ['status' => 'CLOSED'])->assertRedirect();
        $this->actingAs($user)->post('/admin/menu/'.$menu->id.'/approve')->assertRedirect();
    }

    public function test_super_admin_can_access_all(): void
    {
        $user = $this->user('SUPER_ADMIN');
        $this->actingAs($user)->get('/admin/complaints')->assertOk();
        $this->actingAs($user)->get('/admin/menu')->assertOk();
    }

    public function test_member_cannot_access_admin_complaint_or_menu_routes(): void
    {
        $user = $this->user('MEMBER');
        $this->actingAs($user)->get('/admin/complaints')->assertForbidden();
        $this->actingAs($user)->get('/admin/menu')->assertForbidden();
    }
}
