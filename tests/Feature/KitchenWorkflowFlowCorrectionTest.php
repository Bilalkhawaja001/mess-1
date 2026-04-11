<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\KitchenIssue;
use App\Models\MealPlan;
use App\Models\Menu;
use App\Models\Mess;
use App\Models\Role;
use App\Models\StockTransaction;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenWorkflowFlowCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    private function admin(): User
    {
        $role = Role::query()->where('code', 'SUPER_ADMIN')->first() ?? Role::query()->create([
            'code' => 'SUPER_ADMIN',
            'name' => 'Super Admin',
            'is_active' => true,
        ]);

        return User::query()->create([
            'username' => 'flow-admin',
            'name' => 'Flow Admin',
            'email' => 'flow-admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    private function kitchenItem(): Item
    {
        return Item::query()->create([
            'name' => 'Rice',
            'sku' => 'RICE-FLOW',
            'category' => 'Grocery',
            'uom' => 'kg',
            'reorder_level' => 0,
            'is_active' => true,
        ]);
    }

    private function mess(): Mess
    {
        $department = Department::query()->create([
            'name' => 'Ops',
            'code' => 'OPS',
            'is_active' => true,
        ]);

        return Mess::query()->create([
            'name' => 'Main Mess',
            'code' => 'MAIN',
            'department_id' => $department->id,
            'is_active' => true,
        ]);
    }

    public function test_meal_plan_create_defaults_to_draft(): void
    {
        $this->actingAs($this->admin());
        $menu = Menu::query()->create(['name' => 'Lunch', 'meal_type' => 'LUNCH']);

        $this->post(route('admin.kitchen.plans.store'), [
            'plan_date' => '2026-04-11',
            'menu_id' => $menu->id,
            'planned_servings' => 100,
        ])->assertRedirect();

        $this->assertDatabaseHas('meal_plans', [
            'menu_id' => $menu->id,
            'planned_servings' => 100,
            'status' => MealPlan::STATUS_DRAFT,
        ]);
        $this->assertNull(MealPlan::query()->first()->approved_at);
    }

    public function test_meal_plan_approve_changes_status_and_sets_approved_at(): void
    {
        $this->actingAs($this->admin());
        $plan = MealPlan::query()->create([
            'plan_date' => '2026-04-11',
            'menu_id' => Menu::query()->create(['name' => 'Dinner', 'meal_type' => 'DINNER'])->id,
            'planned_servings' => 50,
            'status' => MealPlan::STATUS_DRAFT,
            'approved_at' => null,
        ]);

        $this->post(route('admin.kitchen.plans.approve.legacy', $plan))->assertRedirect();

        $plan->refresh();
        $this->assertSame(MealPlan::STATUS_APPROVED, $plan->status);
        $this->assertNotNull($plan->approved_at);
    }

    public function test_meal_plan_approve_does_not_create_stock_transaction(): void
    {
        $this->actingAs($this->admin());
        $plan = MealPlan::query()->create([
            'plan_date' => '2026-04-11',
            'menu_id' => Menu::query()->create(['name' => 'Breakfast', 'meal_type' => 'BREAKFAST'])->id,
            'planned_servings' => 20,
            'status' => MealPlan::STATUS_DRAFT,
        ]);

        $this->post(route('admin.kitchen.plans.approve.legacy', $plan))->assertRedirect();

        $this->assertDatabaseCount('stock_transactions', 0);
    }

    public function test_issue_create_defaults_to_draft(): void
    {
        $this->actingAs($this->admin());
        $item = $this->kitchenItem();
        $mess = $this->mess();

        $this->post(route('admin.kitchen.issues.store'), [
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'quantity' => 5,
            'unit_code' => 'kg',
            'issue_type' => 'CONSUMPTION',
            'mess_id' => $mess->id,
            'remarks' => 'Draft issue',
        ])->assertRedirect();

        $this->assertDatabaseHas('kitchen_issues', [
            'item_id' => $item->id,
            'status' => KitchenIssue::STATUS_DRAFT,
            'approved_stock_txn_id' => null,
        ]);
        $this->assertNull(KitchenIssue::query()->first()->approved_at);
    }

    public function test_issue_create_does_not_create_stock_transaction(): void
    {
        $this->actingAs($this->admin());
        $item = $this->kitchenItem();
        $mess = $this->mess();

        $this->post(route('admin.kitchen.issues.store'), [
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'quantity' => 3,
            'unit_code' => 'kg',
            'issue_type' => 'CONSUMPTION',
            'mess_id' => $mess->id,
        ])->assertRedirect();

        $this->assertDatabaseCount('stock_transactions', 0);
    }

    public function test_issue_approve_creates_exactly_one_stock_transaction(): void
    {
        $this->actingAs($this->admin());
        $item = $this->kitchenItem();
        $mess = $this->mess();

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'OPENING',
            'quantity' => 50,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);

        $issue = KitchenIssue::query()->create([
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'mess_id' => $mess->id,
            'issue_type' => 'CONSUMPTION',
            'quantity' => 5,
            'remarks' => 'Approve me',
            'status' => KitchenIssue::STATUS_DRAFT,
        ]);

        $this->post(route('admin.kitchen.issues.approve.legacy', $issue))->assertRedirect();

        $this->assertDatabaseCount('stock_transactions', 2);
        $this->assertDatabaseHas('stock_transactions', [
            'item_id' => $item->id,
            'txn_type' => StockTransaction::TXN_TYPE_KITCHEN_ISSUE,
            'reference_type' => KitchenIssue::class,
            'reference_id' => $issue->id,
        ]);
    }

    public function test_issue_approve_changes_status_and_sets_approved_at(): void
    {
        $this->actingAs($this->admin());
        $item = $this->kitchenItem();

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'OPENING',
            'quantity' => 20,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);

        $issue = KitchenIssue::query()->create([
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'issue_type' => 'CONSUMPTION',
            'quantity' => 2,
            'status' => KitchenIssue::STATUS_DRAFT,
        ]);

        $this->post(route('admin.kitchen.issues.approve.legacy', $issue))->assertRedirect();

        $issue->refresh();
        $this->assertSame(KitchenIssue::STATUS_APPROVED, $issue->status);
        $this->assertNotNull($issue->approved_at);
        $this->assertNotNull($issue->approved_stock_txn_id);
    }

    public function test_second_approve_call_does_not_duplicate_stock_transaction(): void
    {
        $this->actingAs($this->admin());
        $item = $this->kitchenItem();

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'OPENING',
            'quantity' => 20,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);

        $issue = KitchenIssue::query()->create([
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'issue_type' => 'CONSUMPTION',
            'quantity' => 2,
            'status' => KitchenIssue::STATUS_DRAFT,
        ]);

        $this->post(route('admin.kitchen.issues.approve.legacy', $issue))->assertRedirect();
        $this->post(route('admin.kitchen.issues.approve.legacy', $issue->fresh()))->assertRedirect();

        $this->assertDatabaseCount('stock_transactions', 2);
        $this->assertEquals(1, StockTransaction::query()->where('txn_type', StockTransaction::TXN_TYPE_KITCHEN_ISSUE)->count());
    }

    public function test_legacy_kitchen_page_still_loads_successfully(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.kitchen.index'))
            ->assertOk()
            ->assertSee('Create Menu')
            ->assertSee('Create Meal Plan')
            ->assertSee('Post Kitchen Issue');
    }

    public function test_existing_recipe_menu_and_plan_creation_still_works(): void
    {
        $this->actingAs($this->admin());
        $item = $this->kitchenItem();

        $this->post(route('admin.kitchen.menus.store'), [
            'name' => 'Special Lunch',
            'meal_type' => 'LUNCH',
        ])->assertRedirect();

        $menu = Menu::query()->firstOrFail();

        $this->post(route('admin.kitchen.recipes.store'), [
            'menu_id' => $menu->id,
            'item_id' => $item->id,
            'qty_per_serving' => 0.5,
        ])->assertRedirect();

        $this->post(route('admin.kitchen.plans.store'), [
            'plan_date' => '2026-04-12',
            'menu_id' => $menu->id,
            'planned_servings' => 60,
        ])->assertRedirect();

        $this->assertDatabaseHas('menus', ['name' => 'Special Lunch']);
        $this->assertDatabaseHas('recipes', ['menu_id' => $menu->id, 'item_id' => $item->id]);
        $this->assertDatabaseHas('meal_plans', ['menu_id' => $menu->id, 'status' => MealPlan::STATUS_DRAFT]);
    }
}
