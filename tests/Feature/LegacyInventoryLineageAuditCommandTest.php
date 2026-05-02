<?php

namespace Tests\Feature;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockTransaction;
use App\Models\Vendor;
use App\Models\VendorReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyInventoryLineageAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_command_reports_legacy_and_ambiguous_lineage_without_mutating_data(): void
    {
        $vendor = Vendor::query()->create(['name' => 'Legacy Vendor']);
        $item = Item::query()->create(['name' => 'Rice', 'sku' => 'LEGACY-RICE', 'uom' => 'kg', 'is_active' => true]);

        $po = PurchaseOrder::query()->create([
            'vendor_id' => $vendor->id,
            'po_number' => 'PO-LEGACY-AUDIT',
            'po_date' => '2026-04-11',
            'status' => 'ISSUED',
        ]);

        $poLineA = PurchaseOrderLine::query()->create([
            'purchase_order_id' => $po->id,
            'item_id' => $item->id,
            'qty_ordered' => 5,
            'unit_price' => 100,
        ]);
        $poLineB = PurchaseOrderLine::query()->create([
            'purchase_order_id' => $po->id,
            'item_id' => $item->id,
            'qty_ordered' => 5,
            'unit_price' => 100,
        ]);

        $grn = GoodsReceipt::query()->create([
            'purchase_order_id' => $po->id,
            'grn_number' => 'GRN-LEGACY-AUDIT',
            'received_date' => '2026-04-12',
        ]);

        $lineAPayload = [
            'goods_receipt_id' => $grn->id,
            'item_id' => $item->id,
            'qty_received' => 5,
            'unit_cost' => 100,
        ];
        if (Schema::hasColumn('goods_receipt_lines', 'purchase_order_line_id')) {
            $lineAPayload['purchase_order_line_id'] = $poLineA->id;
        }
        $lineA = GoodsReceiptLine::query()->create($lineAPayload);

        $lineBPayload = [
            'goods_receipt_id' => $grn->id,
            'item_id' => $item->id,
            'qty_received' => 5,
            'unit_cost' => 100,
        ];
        if (Schema::hasColumn('goods_receipt_lines', 'purchase_order_line_id')) {
            $lineBPayload['purchase_order_line_id'] = null;
        }
        $lineB = GoodsReceiptLine::query()->create($lineBPayload);

        $txn = StockTransaction::query()->create([
            'item_id' => $item->id,
            'txn_type' => 'GRN',
            'quantity' => 5,
            'trans_quantity' => 5,
            'unit_cost' => 100,
            'txn_at' => '2026-04-12',
            'reference_type' => GoodsReceipt::class,
            'reference_id' => $grn->id,
        ]);

        $vendorReturn = VendorReturn::query()->create([
            'return_number' => 'VRN-LEGACY-AUDIT',
            'return_date' => '2026-04-13',
            'vendor_id' => $vendor->id,
            'goods_receipt_id' => $grn->id,
            'item_id' => $item->id,
            'qty_returned' => 2,
            'goods_receipt_line_id' => null,
        ]);

        Artisan::call('audit:legacy-inventory-lineage');
        $output = Artisan::output();
        $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(1, $decoded['summary']['goods_receipt_lines_missing_purchase_order_line_id']);
        $this->assertSame(1, $decoded['summary']['grn_stock_transactions_still_pointing_to_goods_receipts']);
        $this->assertSame(1, $decoded['summary']['vendor_returns_missing_goods_receipt_line_id']);
        $this->assertSame(1, $decoded['summary']['ambiguous_grn_stock_transaction_matches']);
        $this->assertSame(1, $decoded['summary']['ambiguous_vendor_return_matches']);

        $this->assertSame([$lineA->id, $lineB->id], $decoded['details']['ambiguous_grn_stock_transaction_matches'][0]['candidate_goods_receipt_line_ids']);
        $this->assertSame([$lineA->id, $lineB->id], $decoded['details']['ambiguous_vendor_return_matches'][0]['candidate_goods_receipt_line_ids']);

        $this->assertSame(GoodsReceipt::class, $txn->fresh()->reference_type);
        $this->assertNull($vendorReturn->fresh()->goods_receipt_line_id);
        $this->assertNull($lineB->fresh()->purchase_order_line_id);
    }
}
