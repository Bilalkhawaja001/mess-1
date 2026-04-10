<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Item;
use App\Models\KitchenIssue;
use App\Models\Mess;
use App\Models\Role;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Vendor;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\ItemUnit;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenInventoryDesignTest extends TestCase
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
            'username' => 'kitchen-admin',
            'name' => 'Kitchen Admin',
            'email' => 'kitchen-admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_kitchen_issue_posts_stock_once_with_mess_and_type_and_conversion(): void
    {
        $admin = $this->admin();
        $department = Department::query()->create(['name' => 'IT', 'code' => 'IT', 'is_active' => true]);
        $mess = Mess::query()->create(['name' => 'Main Mess', 'code' => 'MAIN', 'department_id' => $department->id, 'is_active' => true]);
        $item = Item::query()->create(['name' => 'Rice', 'sku' => 'RICE-1', 'uom' => 'kg', 'reorder_level' => 0, 'is_active' => true]);

        ItemUnit::query()->create([
            'item_id' => $item->id,
            'unit_code' => 'bag',
            'factor_to_base' => 50.0,
            'is_default_for_grn' => false,
            'is_default_for_kitchen' => true,
        ]);

        // Seed some opening stock so that kitchen issue does not drive stock negative.
        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'OPENING',
            'quantity' => 150,
            'unit_cost' => 0,
            'txn_at' => '2026-04-09',
        ]);

        $this->actingAs($admin);
        $response = $this->post('/admin/kitchen/issues', [
            'issue_date' => '2026-04-10',
            'item_id' => $item->id,
            'quantity' => 2,
            'unit_code' => 'bag',
            'issue_type' => 'CONSUMPTION',
            'mess_id' => $mess->id,
            'remarks' => 'Lunch service',
        ]);

        $response->assertRedirect();

        $issue = KitchenIssue::query()->firstOrFail();
        $this->assertSame('CONSUMPTION', $issue->issue_type);
        $this->assertSame($mess->id, $issue->mess_id);
        $this->assertSame('100.000', number_format((float) $issue->quantity, 3, '.', ''));

        $this->assertDatabaseCount('stock_transactions', 1);
        $txn = StockTransaction::query()->firstOrFail();
        $this->assertSame('KITCHEN_ISSUE', $txn->txn_type);
        $this->assertSame('100.000', number_format((float) $txn->quantity, 3, '.', ''));
        $this->assertSame('bag', $txn->trans_unit_code);
        $this->assertSame('2.000', number_format((float) $txn->trans_quantity, 3, '.', ''));

        $this->post("/admin/kitchen/issues/{$issue->id}/approve")->assertRedirect();
        $this->assertDatabaseCount('stock_transactions', 1);
    }

    public function test_grn_uses_conversion_and_approval_does_not_double_post(): void
    {
        $admin = $this->admin();
        $vendor = Vendor::query()->create(['name' => 'Vendor A']);
        $item = Item::query()->create(['name' => 'Flour', 'sku' => 'FLOUR-1', 'uom' => 'kg', 'is_active' => true]);

        ItemUnit::query()->create([
            'item_id' => $item->id,
            'unit_code' => 'bag',
            'factor_to_base' => 20.0,
            'is_default_for_grn' => true,
            'is_default_for_kitchen' => false,
        ]);

        $po = PurchaseOrder::query()->create([
            'vendor_id' => $vendor->id,
            'po_number' => 'PO-TEST',
            'po_date' => '2026-04-10',
            'status' => 'ISSUED',
        ]);
        $line = PurchaseOrderLine::query()->create([
            'purchase_order_id' => $po->id,
            'item_id' => $item->id,
            'qty_ordered' => 5,
            'unit_price' => 100,
        ]);

        $this->actingAs($admin);
        $response = $this->post('/admin/procurement/grn', [
            'purchase_order_id' => $po->id,
            'purchase_order_line_id' => $line->id,
            'item_id' => $item->id,
            'received_date' => '2026-04-10',
            'qty_received' => 3,
            'unit_cost' => 90,
            'unit_code' => 'bag',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('goods_receipts', 1);
        $this->assertDatabaseCount('goods_receipt_lines', 1);
        $this->assertDatabaseCount('stock_transactions', 1);

        $txn = StockTransaction::query()->firstOrFail();
        $this->assertSame('GRN', $txn->txn_type);
        $this->assertSame('60.000', number_format((float) $txn->quantity, 3, '.', ''));
        $this->assertSame('bag', $txn->trans_unit_code);
        $this->assertSame('3.000', number_format((float) $txn->trans_quantity, 3, '.', ''));

        $grn = GoodsReceipt::query()->firstOrFail();
        $this->post("/admin/procurement/grn/{$grn->id}/approve")->assertRedirect();
        $this->assertDatabaseCount('stock_transactions', 1);
    }

    public function test_low_stock_reporting_and_trail_endpoint(): void
    {
        $admin = $this->admin();
        $item = Item::query()->create(['name' => 'Oil', 'sku' => 'OIL-1', 'uom' => 'ltr', 'reorder_level' => 5, 'is_active' => true]);

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'OPENING',
            'quantity' => 4,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);

        $this->actingAs($admin);
        $inventory = $this->get('/admin/inventory');
        $inventory->assertOk();
        $inventory->assertSee('Low Stock Items');
        $inventory->assertSee('OIL-1');

        $trail = $this->get("/admin/inventory/items/{$item->id}/trail");
        $trail->assertOk();
        $trail->assertSee('Procurement to Consumption Trail');
        $trail->assertSee('Oil');
    }
}
