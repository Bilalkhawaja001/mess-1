<?php

namespace Tests\Feature;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockTransaction;
use App\Models\Vendor;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function adminUser(): User
    {
        $user = User::query()->create([
            'name' => 'Procurement Admin',
            'email' => 'procurement-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $user->permissions()->syncWithoutDetaching(
            \App\Models\Permission::query()->whereIn('name', ['procurement.manage'])->pluck('id')->all()
        );

        return $user;
    }

    public function test_valid_po_creation(): void
    {
        $admin = $this->adminUser();
        $vendor = Vendor::query()->create(['name' => 'Vendor A']);
        $item = Item::query()->create(['name' => 'Rice', 'sku' => 'RICE-1', 'uom' => 'kg', 'is_active' => true]);

        $response = $this->actingAs($admin)->post('/admin/procurement/po', [
            'vendor_id' => $vendor->id,
            'po_date' => '2026-04-10',
            'lines' => [
                ['item_id' => $item->id, 'qty_ordered' => '10', 'unit_price' => '100'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('purchase_orders', 1);
        $this->assertDatabaseCount('purchase_order_lines', 1);
    }

    public function test_invalid_po_creation_without_vendor_item_qty_price(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->from('/admin/procurement')->post('/admin/procurement/po', [
            'vendor_id' => '',
            'po_date' => '',
            'lines' => [
                ['item_id' => '', 'qty_ordered' => '0', 'unit_price' => '0'],
            ],
        ]);

        $response->assertRedirect('/admin/procurement');
        $response->assertSessionHasErrors(['vendor_id', 'po_date', 'lines.0.item_id', 'lines.0.qty_ordered', 'lines.0.unit_price']);
    }

    public function test_valid_grn_creation(): void
    {
        $admin = $this->adminUser();
        [$po, $line] = $this->makePurchaseOrderWithLine();

        $response = $this->actingAs($admin)->post('/admin/procurement/grn', [
            'purchase_order_id' => $po->id,
            'purchase_order_line_id' => $line->id,
            'item_id' => $line->item_id,
            'received_date' => '2026-04-10',
            'qty_received' => '4',
            'unit_cost' => '95',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('goods_receipts', 1);
        $this->assertDatabaseCount('goods_receipt_lines', 1);
        $this->assertDatabaseCount('stock_transactions', 1);
    }

    public function test_grn_blocked_when_qty_exceeds_pending(): void
    {
        $admin = $this->adminUser();
        [$po, $line] = $this->makePurchaseOrderWithLine(5);

        $response = $this->actingAs($admin)->from('/admin/procurement')->post('/admin/procurement/grn', [
            'purchase_order_id' => $po->id,
            'purchase_order_line_id' => $line->id,
            'item_id' => $line->item_id,
            'received_date' => '2026-04-10',
            'qty_received' => '6',
            'unit_cost' => '95',
        ]);

        $response->assertRedirect('/admin/procurement');
        $response->assertSessionHasErrors(['qty_received']);
        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('stock_transactions', 0);
    }

    public function test_grn_blocked_when_pending_zero(): void
    {
        $admin = $this->adminUser();
        [$po, $line] = $this->makePurchaseOrderWithLine(5);
        $this->createGrn($po, $line, 5, 100);

        $response = $this->actingAs($admin)->from('/admin/procurement')->post('/admin/procurement/grn', [
            'purchase_order_id' => $po->id,
            'purchase_order_line_id' => $line->id,
            'item_id' => $line->item_id,
            'received_date' => '2026-04-11',
            'qty_received' => '1',
            'unit_cost' => '100',
        ]);

        $response->assertRedirect('/admin/procurement');
        $response->assertSessionHasErrors(['qty_received']);
        $this->assertDatabaseCount('goods_receipts', 1);
        $this->assertDatabaseCount('stock_transactions', 1);
    }

    public function test_partial_grn_updates_received_pending_correctly(): void
    {
        $admin = $this->adminUser();
        [$po, $line] = $this->makePurchaseOrderWithLine(10);
        $this->createGrn($po, $line, 4, 100);

        $response = $this->actingAs($admin)->get('/admin/procurement');
        $response->assertOk();
        $response->assertSee('4.000');
        $response->assertSee('6.000');
    }

    public function test_multiple_grns_sum_correctly(): void
    {
        $admin = $this->adminUser();
        [$po, $line] = $this->makePurchaseOrderWithLine(10);
        $this->createGrn($po, $line, 4, 100);
        $this->createGrn($po, $line, 3, 100);

        $response = $this->actingAs($admin)->get('/admin/procurement');
        $response->assertOk();
        $response->assertSee('7.000');
        $response->assertSee('3.000');
    }

    public function test_stock_not_double_posted_by_approval_acknowledgement(): void
    {
        $admin = $this->adminUser();
        [$po, $line] = $this->makePurchaseOrderWithLine(5);
        $grn = $this->createGrn($po, $line, 2, 100);

        $this->assertDatabaseCount('stock_transactions', 1);

        $this->actingAs($admin)->post("/admin/procurement/po/{$po->id}/approve")->assertRedirect();
        $this->actingAs($admin)->post("/admin/procurement/grn/{$grn->id}/approve")->assertRedirect();

        $this->assertDatabaseCount('stock_transactions', 1);
    }

    public function test_procurement_page_still_loads(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get('/admin/procurement')->assertOk();
    }

    public function test_existing_approval_flows_do_not_break(): void
    {
        $admin = $this->adminUser();
        [$po, $line] = $this->makePurchaseOrderWithLine(5);
        $grn = $this->createGrn($po, $line, 2, 100);

        $this->actingAs($admin)->post("/admin/procurement/po/{$po->id}/approve")->assertRedirect();
        $this->actingAs($admin)->post("/admin/procurement/grn/{$grn->id}/approve")->assertRedirect();

        $po->refresh();
        $this->assertSame('PARTIALLY_RECEIVED', $po->status);
    }

    private function makePurchaseOrderWithLine(float $qty = 10): array
    {
        $vendor = Vendor::query()->create(['name' => 'Vendor A']);
        $item = Item::query()->create(['name' => 'Rice', 'sku' => 'RICE-1', 'uom' => 'kg', 'is_active' => true]);
        $po = PurchaseOrder::query()->create([
            'vendor_id' => $vendor->id,
            'po_number' => 'PO-'.uniqid(),
            'po_date' => '2026-04-10',
            'status' => 'ISSUED',
        ]);
        $line = PurchaseOrderLine::query()->create([
            'purchase_order_id' => $po->id,
            'item_id' => $item->id,
            'qty_ordered' => $qty,
            'unit_price' => 100,
        ]);

        return [$po, $line];
    }

    private function createGrn(PurchaseOrder $po, PurchaseOrderLine $line, float $qty, float $unitCost): GoodsReceipt
    {
        $grn = GoodsReceipt::query()->create([
            'purchase_order_id' => $po->id,
            'grn_number' => 'GRN-'.uniqid(),
            'received_date' => '2026-04-10',
        ]);

        GoodsReceiptLine::query()->create([
            'goods_receipt_id' => $grn->id,
            'item_id' => $line->item_id,
            'qty_received' => $qty,
            'unit_cost' => $unitCost,
        ]);

        StockTransaction::query()->create([
            'item_id' => $line->item_id,
            'txn_type' => 'GRN',
            'quantity' => $qty,
            'unit_cost' => $unitCost,
            'reference_type' => GoodsReceipt::class,
            'reference_id' => $grn->id,
            'txn_at' => '2026-04-10',
            'remarks' => 'GRN posting (stock posted on create)',
        ]);

        return $grn;
    }
}
