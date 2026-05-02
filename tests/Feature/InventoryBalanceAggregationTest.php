<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\Mess;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Role;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Vendor;
use App\Services\InventoryService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryBalanceAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    private function admin(): User
    {
        $role = Role::query()->where('code', 'SUPER_ADMIN')->first()
            ?? Role::query()->create(['code' => 'SUPER_ADMIN', 'name' => 'Super Admin', 'is_active' => true]);

        return User::query()->create([
            'username' => 'agg-admin',
            'name' => 'Aggregation Admin',
            'email' => 'agg-admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_aggregate_balances_match_single_item_balance_for_sample_items(): void
    {
        $first = Item::query()->create([
            'name' => 'Rice',
            'sku' => 'AGG-RICE',
            'uom' => 'kg',
            'is_active' => true,
        ]);
        $second = Item::query()->create([
            'name' => 'Oil',
            'sku' => 'AGG-OIL',
            'uom' => 'ltr',
            'is_active' => true,
        ]);

        StockTransaction::query()->insert([
            ['item_id' => $first->id, 'txn_type' => 'OPENING', 'quantity' => 10, 'unit_cost' => 0, 'txn_at' => '2026-04-10'],
            ['item_id' => $first->id, 'txn_type' => 'OUT', 'quantity' => 3, 'unit_cost' => 0, 'txn_at' => '2026-04-11'],
            ['item_id' => $second->id, 'txn_type' => 'OPENING', 'quantity' => 8, 'unit_cost' => 0, 'txn_at' => '2026-04-10'],
            ['item_id' => $second->id, 'txn_type' => 'VENDOR_RETURN', 'quantity' => 2, 'unit_cost' => 0, 'txn_at' => '2026-04-11'],
        ]);

        $service = app(InventoryService::class);
        $aggregate = $service->balancesForItems([$first->id, $second->id]);

        $this->assertSame($service->balanceForItem($first->id), (float) $aggregate[$first->id]);
        $this->assertSame($service->balanceForItem($second->id), (float) $aggregate[$second->id]);
    }

    public function test_store_stock_totals_include_transactions_older_than_latest_hundred_rows(): void
    {
        $admin = $this->admin();
        $targetItem = Item::query()->create([
            'name' => 'Historic Rice',
            'sku' => 'HIST-RICE',
            'uom' => 'kg',
            'is_active' => true,
        ]);

        StockTransaction::query()->create([
            'item_id' => $targetItem->id,
            'txn_type' => 'OPENING',
            'quantity' => 100,
            'unit_cost' => 0,
            'txn_at' => '2026-01-01 00:00:00',
            'remarks' => 'opening old stock',
        ]);
        StockTransaction::query()->create([
            'item_id' => $targetItem->id,
            'txn_type' => 'OUT',
            'quantity' => 30,
            'unit_cost' => 0,
            'txn_at' => '2026-01-02 00:00:00',
            'remarks' => 'old issue',
        ]);

        for ($i = 0; $i < 101; $i++) {
            $noiseItem = Item::query()->create([
                'name' => 'Noise '.$i,
                'sku' => 'NOISE-'.$i,
                'uom' => 'kg',
                'is_active' => true,
            ]);

            StockTransaction::query()->create([
                'item_id' => $noiseItem->id,
                'txn_type' => 'OPENING',
                'quantity' => 1,
                'unit_cost' => 0,
                'txn_at' => now()->addSeconds($i),
                'remarks' => 'newer noise txn '.$i,
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin/inventory?tab=store-stock');
        $response->assertOk();

        $row = collect($response->viewData('storeStockRows'))->first(function (array $row) use ($targetItem) {
            return (int) $row['item']->id === $targetItem->id;
        });

        $this->assertNotNull($row);
        $this->assertSame(100.0, (float) $row['received_qty']);
        $this->assertSame(30.0, (float) $row['issued_qty']);
        $this->assertSame(70.0, (float) $row['balance']);
    }

    public function test_vendor_return_source_uses_current_balance_correctly(): void
    {
        $admin = $this->admin();
        $item = Item::query()->create([
            'name' => 'Rice',
            'sku' => 'AGG-VR-RICE',
            'uom' => 'kg',
            'is_active' => true,
        ]);

        [$grn, $line] = $this->seedGrnLine($item, 5, 100, '2026-04-10');

        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'OUT',
            'quantity' => 4,
            'unit_cost' => 0,
            'txn_at' => '2026-04-11',
            'remarks' => 'consume stock',
        ]);

        $response = $this->actingAs($admin)->get('/admin/inventory?tab=vendor-return');
        $response->assertOk();

        $source = collect($response->viewData('vendorReturnSources'))->firstWhere('goods_receipt_line_id', $line->id);
        $this->assertNotNull($source);
        $this->assertSame($grn->id, $source['goods_receipt_id']);
        $this->assertSame(1.0, (float) $source['current_balance_qty']);
        $this->assertSame(1.0, (float) $source['returnable_qty']);
    }

    public function test_kitchen_issue_rejects_zero_stock_item_but_allows_item_with_balance(): void
    {
        $admin = $this->admin();
        $department = Department::query()->create([
            'name' => 'Ops',
            'code' => 'OPS',
            'is_active' => true,
        ]);
        $mess = Mess::query()->create([
            'name' => 'Main Mess',
            'code' => 'MAIN',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $withStock = Item::query()->create([
            'name' => 'Rice Stocked',
            'sku' => 'KITCHEN-STOCKED',
            'uom' => 'kg',
            'is_active' => true,
        ]);
        $zeroStock = Item::query()->create([
            'name' => 'Rice Empty',
            'sku' => 'KITCHEN-EMPTY',
            'uom' => 'kg',
            'is_active' => true,
        ]);

        StockTransaction::query()->create([
            'item_id' => $withStock->id,
            'txn_type' => 'OPENING',
            'quantity' => 5,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);
        StockTransaction::query()->create([
            'item_id' => $zeroStock->id,
            'txn_type' => 'OPENING',
            'quantity' => 5,
            'unit_cost' => 0,
            'txn_at' => '2026-04-10',
        ]);
        StockTransaction::query()->create([
            'item_id' => $zeroStock->id,
            'txn_type' => 'OUT',
            'quantity' => 5,
            'unit_cost' => 0,
            'txn_at' => '2026-04-11',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.kitchen.issues.store'), [
                'issue_date' => '2026-04-12',
                'item_id' => $withStock->id,
                'quantity' => 1,
                'issue_type' => 'CONSUMPTION',
                'mess_id' => $mess->id,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.kitchen.index'))
            ->post(route('admin.kitchen.issues.store'), [
                'issue_date' => '2026-04-12',
                'item_id' => $zeroStock->id,
                'quantity' => 1,
                'issue_type' => 'CONSUMPTION',
                'mess_id' => $mess->id,
            ])
            ->assertRedirect(route('admin.kitchen.index'))
            ->assertSessionHasErrors(['quantity']);

        $this->assertDatabaseHas('kitchen_issues', [
            'item_id' => $withStock->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseMissing('kitchen_issues', [
            'item_id' => $zeroStock->id,
        ]);
    }

    private function seedGrnLine(Item $item, float $qty, float $unitCost, string $receivedDate): array
    {
        $vendor = Vendor::query()->create(['name' => 'Vendor AGG '.uniqid()]);
        $po = PurchaseOrder::query()->create([
            'vendor_id' => $vendor->id,
            'po_number' => 'PO-AGG-'.uniqid(),
            'po_date' => $receivedDate,
            'status' => 'ISSUED',
        ]);
        $poLine = PurchaseOrderLine::query()->create([
            'purchase_order_id' => $po->id,
            'item_id' => $item->id,
            'qty_ordered' => $qty,
            'unit_price' => $unitCost,
        ]);
        $grn = GoodsReceipt::query()->create([
            'purchase_order_id' => $po->id,
            'grn_number' => 'GRN-AGG-'.uniqid(),
            'received_date' => $receivedDate,
        ]);

        $payload = [
            'goods_receipt_id' => $grn->id,
            'item_id' => $item->id,
            'qty_received' => $qty,
            'unit_cost' => $unitCost,
        ];
        if (Schema::hasColumn('goods_receipt_lines', 'purchase_order_line_id')) {
            $payload['purchase_order_line_id'] = $poLine->id;
        }

        $line = GoodsReceiptLine::query()->create($payload);
        StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'GRN',
            'quantity' => $qty,
            'unit_cost' => $unitCost,
            'trans_quantity' => $qty,
            'reference_type' => GoodsReceiptLine::class,
            'reference_id' => $line->id,
            'txn_at' => $receivedDate,
            'remarks' => 'seed aggregate grn line stock',
        ]);

        return [$grn, $line];
    }
}
