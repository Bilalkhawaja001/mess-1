<?php

namespace Tests\Feature;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Role;
use App\Models\StockTransaction;
use App\Models\Vendor;
use App\Models\VendorReturn;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryVendorReturnLineSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    private function adminUser(): User
    {
        $role = Role::query()->where('code', 'SUPER_ADMIN')->first()
            ?? Role::query()->create(['code' => 'SUPER_ADMIN', 'name' => 'Super Admin', 'is_active' => true]);

        return User::query()->create([
            'username' => 'inventory-admin',
            'name' => 'Inventory Admin',
            'email' => 'inventory-admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_multi_line_grn_shows_every_line_as_vendor_return_source(): void
    {
        $admin = $this->adminUser();
        [$grn, $lines] = $this->seedGrnWithLines([
            ['sku' => 'RICE-VR', 'name' => 'Rice', 'uom' => 'kg', 'qty' => 5, 'price' => 100],
            ['sku' => 'OIL-VR', 'name' => 'Oil', 'uom' => 'ltr', 'qty' => 3, 'price' => 200],
        ]);

        $response = $this->actingAs($admin)->get('/admin/inventory?tab=vendor-return');
        $response->assertOk();

        $sources = collect($response->viewData('vendorReturnSources'));
        $this->assertCount(2, $sources);
        $this->assertEqualsCanonicalizing($lines->pluck('id')->all(), $sources->pluck('goods_receipt_line_id')->all());
        $this->assertEqualsCanonicalizing([$grn->id, $grn->id], $sources->pluck('goods_receipt_id')->all());
    }

    public function test_partial_return_keeps_same_line_source_available(): void
    {
        $admin = $this->adminUser();
        [, $lines] = $this->seedGrnWithLines([
            ['sku' => 'RICE-PARTIAL', 'name' => 'Rice', 'uom' => 'kg', 'qty' => 5, 'price' => 100],
        ]);
        $line = $lines->first();

        $this->actingAs($admin)->post('/admin/inventory/vendor-returns', [
            'goods_receipt_line_id' => $line->id,
            'return_date' => '2026-04-12',
            'quantity' => 2,
            'unit_code' => '',
            'vendor_id' => 999999,
            'item_id' => 999999,
            'remarks' => 'partial',
        ])->assertRedirect();

        $response = $this->actingAs($admin)->get('/admin/inventory?tab=vendor-return');
        $response->assertOk();
        $sources = collect($response->viewData('vendorReturnSources'));
        $source = $sources->firstWhere('goods_receipt_line_id', $line->id);
        $this->assertNotNull($source);
        $this->assertSame(3.0, (float) $source['returnable_qty']);
    }

    public function test_full_return_removes_line_source_from_selectable_sources(): void
    {
        $admin = $this->adminUser();
        [, $lines] = $this->seedGrnWithLines([
            ['sku' => 'RICE-FULL', 'name' => 'Rice', 'uom' => 'kg', 'qty' => 5, 'price' => 100],
        ]);
        $line = $lines->first();

        $this->actingAs($admin)->post('/admin/inventory/vendor-returns', [
            'goods_receipt_line_id' => $line->id,
            'return_date' => '2026-04-12',
            'quantity' => 5,
            'unit_code' => '',
            'remarks' => 'full',
        ])->assertRedirect();

        $response = $this->actingAs($admin)->get('/admin/inventory?tab=vendor-return');
        $response->assertOk();
        $sources = collect($response->viewData('vendorReturnSources'));
        $this->assertNull($sources->firstWhere('goods_receipt_line_id', $line->id));
    }

    public function test_same_item_in_two_grns_remains_independent_vendor_return_source(): void
    {
        $admin = $this->adminUser();
        [$firstGrn, $firstLines] = $this->seedGrnWithLines([
            ['sku' => 'RICE-TWO-GRN', 'name' => 'Rice', 'uom' => 'kg', 'qty' => 5, 'price' => 100],
        ], '2026-04-10');
        [$secondGrn, $secondLines] = $this->seedGrnWithLines([
            ['sku' => 'RICE-TWO-GRN', 'name' => 'Rice', 'uom' => 'kg', 'qty' => 4, 'price' => 105],
        ], '2026-04-11', $firstLines->first()->item);

        $response = $this->actingAs($admin)->get('/admin/inventory?tab=vendor-return');
        $response->assertOk();

        $sources = collect($response->viewData('vendorReturnSources'));
        $this->assertNotNull($sources->firstWhere('goods_receipt_line_id', $firstLines->first()->id));
        $this->assertNotNull($sources->firstWhere('goods_receipt_line_id', $secondLines->first()->id));
        $this->assertEqualsCanonicalizing([$firstGrn->id, $secondGrn->id], $sources->pluck('goods_receipt_id')->all());
    }

    public function test_tampered_vendor_and_item_inputs_are_ignored_backend_derives_real_source(): void
    {
        $admin = $this->adminUser();
        [$grn, $lines, $vendor] = $this->seedGrnWithLines([
            ['sku' => 'RICE-TAMPER', 'name' => 'Rice', 'uom' => 'kg', 'qty' => 5, 'price' => 100],
        ]);
        $line = $lines->first();

        $otherVendor = Vendor::query()->create(['name' => 'Other Vendor']);
        $otherItem = Item::query()->create(['name' => 'Wrong Item', 'sku' => 'WRONG-ITEM', 'uom' => 'kg', 'is_active' => true]);

        $this->actingAs($admin)->post('/admin/inventory/vendor-returns', [
            'goods_receipt_line_id' => $line->id,
            'return_date' => '2026-04-12',
            'quantity' => 1,
            'vendor_id' => $otherVendor->id,
            'item_id' => $otherItem->id,
            'goods_receipt_id' => 999999,
            'remarks' => 'tampered',
        ])->assertRedirect();

        $return = VendorReturn::query()->latest('id')->firstOrFail();
        $this->assertSame($vendor->id, $return->vendor_id);
        $this->assertSame($grn->id, $return->goods_receipt_id);
        $this->assertSame($line->id, $return->goods_receipt_line_id);
        $this->assertSame($line->item_id, $return->item_id);
    }

    public function test_vendor_return_qty_above_source_pending_fails(): void
    {
        $admin = $this->adminUser();
        [, $lines] = $this->seedGrnWithLines([
            ['sku' => 'RICE-PENDING', 'name' => 'Rice', 'uom' => 'kg', 'qty' => 5, 'price' => 100],
        ]);
        $line = $lines->first();

        $response = $this->actingAs($admin)->from('/admin/inventory?tab=vendor-return')->post('/admin/inventory/vendor-returns', [
            'goods_receipt_line_id' => $line->id,
            'return_date' => '2026-04-12',
            'quantity' => 6,
        ]);

        $response->assertRedirect('/admin/inventory?tab=vendor-return');
        $response->assertSessionHasErrors(['quantity']);
        $this->assertDatabaseCount('vendor_returns', 0);
    }

    public function test_vendor_return_qty_above_current_stock_fails(): void
    {
        $admin = $this->adminUser();
        [, $lines] = $this->seedGrnWithLines([
            ['sku' => 'RICE-STOCK', 'name' => 'Rice', 'uom' => 'kg', 'qty' => 5, 'price' => 100],
        ]);
        $line = $lines->first();

        StockTransaction::query()->create([
            'item_id' => $line->item_id,
            'txn_type' => 'OUT',
            'quantity' => 4,
            'unit_cost' => 0,
            'txn_at' => '2026-04-11',
            'remarks' => 'consume stock',
        ]);

        $response = $this->actingAs($admin)->from('/admin/inventory?tab=vendor-return')->post('/admin/inventory/vendor-returns', [
            'goods_receipt_line_id' => $line->id,
            'return_date' => '2026-04-12',
            'quantity' => 2,
        ]);

        $response->assertRedirect('/admin/inventory?tab=vendor-return');
        $response->assertSessionHasErrors(['quantity']);
        $this->assertDatabaseCount('vendor_returns', 0);
    }

    private function seedGrnWithLines(array $lineDefs, string $receivedDate = '2026-04-10', ?Item $sharedItem = null): array
    {
        $vendor = Vendor::query()->create(['name' => 'Vendor VR '.uniqid()]);
        $po = PurchaseOrder::query()->create([
            'vendor_id' => $vendor->id,
            'po_number' => 'PO-VR-'.uniqid(),
            'po_date' => $receivedDate,
            'status' => 'ISSUED',
        ]);
        $grn = GoodsReceipt::query()->create([
            'purchase_order_id' => $po->id,
            'grn_number' => 'GRN-VR-'.uniqid(),
            'received_date' => $receivedDate,
        ]);

        $lines = collect();
        foreach ($lineDefs as $index => $def) {
            $item = $sharedItem ?? Item::query()->create([
                'name' => $def['name'],
                'sku' => $def['sku'].($sharedItem ? '' : '-'.$index.'-'.uniqid()),
                'uom' => $def['uom'],
                'is_active' => true,
            ]);

            $poLine = PurchaseOrderLine::query()->create([
                'purchase_order_id' => $po->id,
                'item_id' => $item->id,
                'qty_ordered' => $def['qty'],
                'unit_price' => $def['price'],
            ]);

            $payload = [
                'goods_receipt_id' => $grn->id,
                'item_id' => $item->id,
                'qty_received' => $def['qty'],
                'unit_cost' => $def['price'],
            ];
            if (Schema::hasColumn('goods_receipt_lines', 'purchase_order_line_id')) {
                $payload['purchase_order_line_id'] = $poLine->id;
            }

            $line = GoodsReceiptLine::query()->create($payload);
            StockTransaction::query()->create([
                'item_id' => $item->id,
                'txn_type' => 'GRN',
                'quantity' => $def['qty'],
                'unit_cost' => $def['price'],
                'trans_quantity' => $def['qty'],
                'reference_type' => GoodsReceiptLine::class,
                'reference_id' => $line->id,
                'txn_at' => $receivedDate,
                'remarks' => 'seed grn line stock',
            ]);

            $line->setRelation('item', $item);
            $lines->push($line);
        }

        return [$grn, $lines, $vendor];
    }
}
