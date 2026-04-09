<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockTransaction;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcurementController extends Controller
{
    public function index()
    {
        $vendors = Vendor::all();
        $items = Item::query()->where('is_active', true)->orderBy('sku')->get();
        $pos = PurchaseOrder::query()
            ->with(['vendor', 'lines.item', 'goodsReceipts.lines'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(function (PurchaseOrder $po) {
                $line = $po->lines->first();
                $ordered = (float) ($line->qty_ordered ?? 0);
                $received = (float) $po->goodsReceipts->flatMap->lines->sum('qty_received');
                $pending = max($ordered - $received, 0);

                $po->setAttribute('primary_line', $line);
                $po->setAttribute('received_qty', $received);
                $po->setAttribute('pending_qty', $pending);

                return $po;
            });
        $grns = GoodsReceipt::query()->with(['purchaseOrder.vendor', 'lines.item'])->latest()->limit(50)->get();

        return view('admin.procurement.index', compact('vendors', 'items', 'pos', 'grns'));
    }

    public function storeVendor(Request $r): RedirectResponse
    {
        Vendor::create($r->validate(['name' => 'required']));

        return back()->with('success', 'Vendor created');
    }

    public function storePo(Request $r): RedirectResponse
    {
        $d = $r->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'po_date' => 'required|date',
            'item_id' => 'required|exists:items,id',
            'qty_ordered' => 'required|numeric|min:0.001',
        ]);

        DB::transaction(function () use ($d, $r, &$po) {
            $po = PurchaseOrder::create([
                'vendor_id' => $d['vendor_id'],
                'po_number' => 'PO-'.now()->format('YmdHis'),
                'po_date' => $d['po_date'],
                'status' => 'ISSUED',
                'remarks' => $r->input('remarks'),
            ]);
            PurchaseOrderLine::create([
                'purchase_order_id' => $po->id,
                'item_id' => $d['item_id'],
                'qty_ordered' => $d['qty_ordered'],
                'unit_price' => $r->input('unit_price', 0),
            ]);
        });

        return back()->with('success', 'PO created');
    }

    public function approvePo(PurchaseOrder $po): RedirectResponse
    {
        $po->status = 'APPROVED';
        $po->save();

        return back()->with('success', 'PO approved. Current schema has no deeper approval posting beyond status transition.');
    }

    public function storeGrn(Request $r): RedirectResponse
    {
        $d = $r->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'item_id' => 'required|exists:items,id',
            'received_date' => 'required|date',
            'qty_received' => 'required|numeric|min:0.001',
        ]);

        $po = PurchaseOrder::query()->with(['lines', 'goodsReceipts.lines'])->findOrFail($d['purchase_order_id']);
        $poLine = $po->lines->first();

        if (! $poLine) {
            return back()->withErrors(['purchase_order_id' => 'Selected PO has no item line.']);
        }

        if ((int) $poLine->item_id !== (int) $d['item_id']) {
            return back()->withErrors(['item_id' => 'Selected item does not match the PO item.'])->withInput();
        }

        $orderedQty = (float) $poLine->qty_ordered;
        $receivedQty = (float) $po->goodsReceipts->flatMap->lines->sum('qty_received');
        $pendingQty = max($orderedQty - $receivedQty, 0);

        if ($pendingQty <= 0) {
            return back()->withErrors(['qty_received' => 'This PO line is already fully received.'])->withInput();
        }

        if ((float) $d['qty_received'] > $pendingQty) {
            return back()->withErrors(['qty_received' => 'Receive quantity cannot exceed pending quantity.'])->withInput();
        }

        DB::transaction(function () use ($d, $r, &$grn) {
            $grn = GoodsReceipt::create([
                'purchase_order_id' => $d['purchase_order_id'],
                'grn_number' => 'GRN-'.now()->format('YmdHis'),
                'received_date' => $d['received_date'],
                'remarks' => $r->input('remarks'),
            ]);
            GoodsReceiptLine::create([
                'goods_receipt_id' => $grn->id,
                'item_id' => $d['item_id'],
                'qty_received' => $d['qty_received'],
                'unit_cost' => $r->input('unit_cost', 0),
            ]);
            StockTransaction::create([
                'item_id' => $d['item_id'],
                'txn_type' => 'GRN',
                'quantity' => $d['qty_received'],
                'unit_cost' => $r->input('unit_cost', 0),
                'reference_type' => GoodsReceipt::class,
                'reference_id' => $grn->id,
                'txn_at' => $d['received_date'],
                'remarks' => 'GRN posting (stock posted on create)',
            ]);

            $po = PurchaseOrder::query()->with(['lines', 'goodsReceipts.lines'])->findOrFail($d['purchase_order_id']);
            $orderedQty = (float) optional($po->lines->first())->qty_ordered;
            $receivedQty = (float) $po->goodsReceipts->flatMap->lines->sum('qty_received');

            PurchaseOrder::whereKey($d['purchase_order_id'])->update([
                'status' => $receivedQty < $orderedQty ? 'PARTIALLY_RECEIVED' : 'RECEIVED',
            ]);
        });

        return back()->with('success', 'GRN posted');
    }

    public function approveGrn(GoodsReceipt $grn): RedirectResponse
    {
        PurchaseOrder::whereKey($grn->purchase_order_id)->update(['status' => 'RECEIVED']);
        $grn->touch();

        return back()->with('success', 'GRN approval acknowledged. Stock was already posted on GRN create; no extra approval side-effect exists in current schema.');
    }
}
