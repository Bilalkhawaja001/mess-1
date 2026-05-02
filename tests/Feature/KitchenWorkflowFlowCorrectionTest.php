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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

    private function createDailyMenu(string $title = 'Lunch', string $mealType = 'LUNCH'): Menu
    {
        return Menu::query()->create([
            'menu_date' => '2026-04-11',
            'meal_type' => $mealType,
            'title' => $title,
            'description' => null,
            'items_text' => $title,
            'status' => Menu::STATUS_DRAFT,
        ]);
    }

    private function createLegacyMenu(string $title = 'Lunch', string $mealType = 'LUNCH'): int
    {
        return (int) DB::table('menus')->insertGetId([
            'name' => $title,
            'meal_type' => $mealType,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_meal_plan_create_defaults_to_draft(): void
    {
        $this->actingAs($this->admin());
        $this->createDailyMenu('Lunch', 'LUNCH');
        $legacyMenuId = $this->createLegacyMenu('Lunch', 'LUNCH');

        $this->post(route('admin.kitchen.plans.store'), [
            'plan_date' => '2026-04-11',
            'menu_id' => $legacyMenuId,
            'planned_servings' => 100,
        ])->assertRedirect();

        $this->assertDatabaseHas('meal_plans', [
            'menu_id' => $legacyMenuId,
            'planned_servings' => 100,
            'status' => MealPlan::STATUS_DRAFT,
        ]);
        $this->assertNull(MealPlan::query()->first()->approved_at);
    }

    public function test_meal_plan_approve_changes_status_and_sets_approved_at(): void
    {
        $this->actingAs($this->admin());
        $this->createDailyMenu('Dinner', 'DINNER');
        $plan = MealPlan::query()->create([
            'plan_date' => '2026-04-11',
            'menu_id' => $this->createLegacyMenu('Dinner', 'DINNER'),
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
        $this->createDailyMenu('Breakfast', 'BREAKFAST');
        $plan = MealPlan::query()->create([
            'plan_date' => '2026-04-11',
            'menu_id' => $this->createLegacyMenu('Breakfast', 'BREAKFAST'),
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

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'OPENING',
            'quantity' => 10,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);

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

    public function test_duplicate_pending_issue_blocked_after_time_window(): void
    {
        $this->actingAs($this->admin());
        $item = $this->kitchenItem();
        $mess = $this->mess();

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'OPENING',
            'quantity' => 20,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);

        KitchenIssue::query()->create([
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'quantity' => 5,
            'mess_id' => $mess->id,
            'issue_type' => 'CONSUMPTION',
            'remarks' => 'Original request',
            'status' => KitchenIssue::STATUS_DRAFT,
            'created_at' => Carbon::parse('2026-04-11 10:00:00'),
            'updated_at' => Carbon::parse('2026-04-11 10:00:00'),
        ]);

        Carbon::setTestNow('2026-04-11 10:05:00');

        try {
            $this->post(route('admin.kitchen.issues.store'), [
                'issue_date' => '2026-04-11',
                'item_id' => $item->id,
                'quantity' => 5,
                'unit_code' => 'kg',
                'issue_type' => 'CONSUMPTION',
                'mess_id' => $mess->id,
                'remarks' => 'Later duplicate',
            ])
                ->assertRedirect()
                ->assertSessionHasErrors(['kitchen_issue']);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame(1, KitchenIssue::query()->count());
        $this->assertDatabaseHas('kitchen_issues', [
            'item_id' => $item->id,
            'quantity' => 5,
            'mess_id' => $mess->id,
            'issue_type' => 'CONSUMPTION',
            'status' => KitchenIssue::STATUS_DRAFT,
        ]);
    }

    public function test_duplicate_pending_issue_with_different_remarks_is_still_blocked(): void
    {
        $this->actingAs($this->admin());
        $item = $this->kitchenItem();
        $mess = $this->mess();

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'OPENING',
            'quantity' => 20,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);

        $this->post(route('admin.kitchen.issues.store'), [
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'quantity' => 5,
            'unit_code' => 'kg',
            'issue_type' => 'CONSUMPTION',
            'mess_id' => $mess->id,
            'remarks' => 'First remarks',
        ])->assertRedirect();

        $this->post(route('admin.kitchen.issues.store'), [
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'quantity' => 5,
            'unit_code' => 'kg',
            'issue_type' => 'CONSUMPTION',
            'mess_id' => $mess->id,
            'remarks' => 'Second remarks',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors(['kitchen_issue']);

        $this->assertSame(1, KitchenIssue::query()->count());
    }

    public function test_same_issue_after_approval_is_allowed(): void
    {
        $this->actingAs($this->admin());
        $item = $this->kitchenItem();
        $mess = $this->mess();

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'OPENING',
            'quantity' => 20,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);

        $approvedIssue = KitchenIssue::query()->create([
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'quantity' => 5,
            'mess_id' => $mess->id,
            'issue_type' => 'CONSUMPTION',
            'remarks' => 'Already approved',
            'status' => KitchenIssue::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $this->post(route('admin.kitchen.issues.store'), [
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'quantity' => 5,
            'unit_code' => 'kg',
            'issue_type' => 'CONSUMPTION',
            'mess_id' => $mess->id,
            'remarks' => 'Repeat after approval',
        ])->assertRedirect();

        $this->assertSame(2, KitchenIssue::query()->count());
        $this->assertSame(KitchenIssue::STATUS_APPROVED, $approvedIssue->fresh()->status);
        $this->assertSame(1, KitchenIssue::query()->where('status', KitchenIssue::STATUS_DRAFT)->count());
    }

    public function test_different_issue_business_keys_are_allowed(): void
    {
        $this->actingAs($this->admin());
        $item = $this->kitchenItem();
        $otherItem = Item::query()->create([
            'name' => 'Oil',
            'sku' => 'OIL-FLOW',
            'category' => 'Grocery',
            'uom' => 'ltr',
            'reorder_level' => 0,
            'is_active' => true,
        ]);
        $mess = $this->mess();
        $otherDepartment = Department::query()->create([
            'name' => 'Plant',
            'code' => 'PLANT',
            'is_active' => true,
        ]);
        $otherMess = Mess::query()->create([
            'name' => 'Plant Mess',
            'code' => 'PLANT-MESS',
            'department_id' => $otherDepartment->id,
            'is_active' => true,
        ]);

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'OPENING',
            'quantity' => 20,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);
        StockTransaction::query()->create([
            'item_id' => $otherItem->id,
            'txn_type' => 'OPENING',
            'quantity' => 20,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);

        KitchenIssue::query()->create([
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'quantity' => 5,
            'mess_id' => $mess->id,
            'issue_type' => 'CONSUMPTION',
            'remarks' => 'Base request',
            'status' => KitchenIssue::STATUS_DRAFT,
        ]);

        $variants = [
            ['issue_date' => '2026-04-12', 'item_id' => $item->id, 'quantity' => 5, 'unit_code' => 'kg', 'issue_type' => 'CONSUMPTION', 'mess_id' => $mess->id, 'remarks' => 'Different date'],
            ['issue_date' => '2026-04-11', 'item_id' => $otherItem->id, 'quantity' => 5, 'unit_code' => 'ltr', 'issue_type' => 'CONSUMPTION', 'mess_id' => $mess->id, 'remarks' => 'Different item'],
            ['issue_date' => '2026-04-11', 'item_id' => $item->id, 'quantity' => 6, 'unit_code' => 'kg', 'issue_type' => 'CONSUMPTION', 'mess_id' => $mess->id, 'remarks' => 'Different qty'],
            ['issue_date' => '2026-04-11', 'item_id' => $item->id, 'quantity' => 5, 'unit_code' => 'kg', 'issue_type' => 'WASTAGE', 'mess_id' => $mess->id, 'remarks' => 'Different type'],
            ['issue_date' => '2026-04-11', 'item_id' => $item->id, 'quantity' => 5, 'unit_code' => 'kg', 'issue_type' => 'CONSUMPTION', 'mess_id' => $otherMess->id, 'remarks' => 'Different mess'],
        ];

        foreach ($variants as $payload) {
            $this->post(route('admin.kitchen.issues.store'), $payload)
                ->assertRedirect()
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(6, KitchenIssue::query()->count());
    }

    public function test_two_pending_issues_exceeding_combined_stock_cannot_both_approve(): void
    {
        $this->actingAs($this->admin());
        $item = $this->kitchenItem();

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'OPENING',
            'quantity' => 10,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);

        $firstIssue = KitchenIssue::query()->create([
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'issue_type' => 'CONSUMPTION',
            'quantity' => 6,
            'status' => KitchenIssue::STATUS_DRAFT,
        ]);

        $secondIssue = KitchenIssue::query()->create([
            'issue_date' => '2026-04-11',
            'item_id' => $item->id,
            'issue_type' => 'CONSUMPTION',
            'quantity' => 6,
            'status' => KitchenIssue::STATUS_DRAFT,
        ]);

        $this->post(route('admin.kitchen.issues.approve.legacy', $firstIssue))->assertRedirect();

        $this->post(route('admin.kitchen.issues.approve.legacy', $secondIssue))
            ->assertRedirect()
            ->assertSessionHasErrors(['kitchen_issue']);

        $this->assertSame(KitchenIssue::STATUS_APPROVED, $firstIssue->fresh()->status);
        $this->assertSame(KitchenIssue::STATUS_DRAFT, $secondIssue->fresh()->status);
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

    public function test_daily_menu_create_and_update_use_daily_menus_schema_fields(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.kitchen.menus.store'), [
            'name' => 'Special Lunch',
            'meal_type' => 'LUNCH',
        ])->assertRedirect();

        $menu = Menu::query()->firstOrFail();
        $this->assertSame('Special Lunch', $menu->title);
        $this->assertSame('LUNCH', $menu->meal_type);
        $this->assertSame('Special Lunch', $menu->items_text);
        $this->assertDatabaseMissing('daily_menus', ['name' => 'Special Lunch']);

        $this->post(route('admin.kitchen.menus.edit.legacy', $menu), [
            'name' => 'Updated Lunch',
            'meal_type' => 'DINNER',
            'is_active' => '1',
        ])->assertRedirect();

        $menu->refresh();
        $this->assertSame('Updated Lunch', $menu->title);
        $this->assertSame('DINNER', $menu->meal_type);
        $this->assertDatabaseHas('daily_menus', [
            'id' => $menu->id,
            'title' => 'Updated Lunch',
            'meal_type' => 'DINNER',
        ]);
        $this->assertDatabaseMissing('daily_menus', ['name' => 'Updated Lunch']);
    }

    public function test_recipe_creation_requires_legacy_menu_id_when_fk_targets_legacy_menus(): void
    {
        $this->actingAs($this->admin());
        $item = $this->kitchenItem();
        $this->createDailyMenu('Daily Only', 'LUNCH');
        $legacyMenuId = $this->createLegacyMenu('Legacy Lunch', 'LUNCH');

        $this->post(route('admin.kitchen.recipes.store'), [
            'menu_id' => $legacyMenuId,
            'item_id' => $item->id,
            'qty_per_serving' => 0.5,
        ])->assertRedirect();

        $this->assertDatabaseHas('recipes', ['menu_id' => $legacyMenuId, 'item_id' => $item->id]);
    }

    public function test_meal_plan_creation_requires_legacy_menu_id_when_fk_targets_legacy_menus(): void
    {
        $this->actingAs($this->admin());
        $this->createDailyMenu('Daily Only', 'DINNER');
        $legacyMenuId = $this->createLegacyMenu('Legacy Dinner', 'DINNER');

        $this->post(route('admin.kitchen.plans.store'), [
            'plan_date' => '2026-04-12',
            'menu_id' => $legacyMenuId,
            'planned_servings' => 60,
        ])->assertRedirect();

        $this->assertDatabaseHas('meal_plans', [
            'menu_id' => $legacyMenuId,
            'plan_date' => '2026-04-12',
            'planned_servings' => 60,
            'status' => MealPlan::STATUS_DRAFT,
        ]);
    }

    public function test_kitchen_page_uses_daily_menus_for_menu_crud_and_legacy_menus_for_recipe_and_plan_selects(): void
    {
        $this->actingAs($this->admin());
        $this->createDailyMenu('Daily Visible', 'LUNCH');
        $legacyMenuId = $this->createLegacyMenu('Legacy Visible', 'DINNER');

        $response = $this->get(route('admin.kitchen.index'));
        $response->assertOk();
        $response->assertSee('Legacy Visible');

        $legacyOptions = collect($response->viewData('legacyMenuOptions'));
        $menus = $response->viewData('menus');

        $this->assertNotNull($menus->firstWhere('title', 'Daily Visible'));
        $this->assertNull($menus->firstWhere('title', 'Legacy Visible'));
        $this->assertNotNull($legacyOptions->firstWhere('id', $legacyMenuId));
        $this->assertSame('Legacy Visible', $legacyOptions->firstWhere('id', $legacyMenuId)->name);
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
        $legacyMenuId = $this->createLegacyMenu('Special Lunch', 'LUNCH');

        $this->post(route('admin.kitchen.recipes.store'), [
            'menu_id' => $legacyMenuId,
            'item_id' => $item->id,
            'qty_per_serving' => 0.5,
        ])->assertRedirect();

        $this->post(route('admin.kitchen.plans.store'), [
            'plan_date' => '2026-04-12',
            'menu_id' => $legacyMenuId,
            'planned_servings' => 60,
        ])->assertRedirect();

        $this->assertDatabaseHas('daily_menus', ['title' => 'Special Lunch']);
        $this->assertDatabaseHas('recipes', ['menu_id' => $legacyMenuId, 'item_id' => $item->id]);
        $this->assertDatabaseHas('meal_plans', ['menu_id' => $legacyMenuId, 'status' => MealPlan::STATUS_DRAFT]);
    }
}
