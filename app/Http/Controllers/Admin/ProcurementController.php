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
                $receivedByItem = $po->goodsReceipts
                    ->flatMap->lines
                    ->groupBy('item_id')
                    ->map(fn ($lines) => (float) $lines->sum('qty_received'));

                $totalOrdered = (float) $po->lines->sum('qty_ordered');
                $totalReceived = (float) $po->goodsReceipts->flatMap->lines->sum('qty_received');
                $totalPending = max($totalOrdered - $totalReceived, 0);

                $po->lines->transform(function (PurchaseOrderLine $line) use ($receivedByItem) {
                    $receivedQty = (float) ($receivedByItem[$line->item_id] ?? 0);
                    $pendingQty = max((float) $line->qty_ordered - $receivedQty, 0);

                    $line->setAttribute('received_qty', $receivedQty);
                    $line->setAttribute('pending_qty', $pendingQty);

                    return $line;
                });

                $po->setAttribute('total_lines', $po->lines->count());
                $po->setAttribute('total_qty', $totalOrdered);
                $po->setAttribute('total_amount', (float) $po->lines->sum(fn ($line) => ((float) $line->qty_ordered) * ((float) $line->unit_price)));
                $po->setAttribute('received_qty', $totalReceived);
                $po->setAttribute('pending_qty', $totalPending);

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
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.qty_ordered' => 'required|numeric|gt:0',
            'lines.*.unit_price' => 'required|numeric|gt:0',
        ]);

        $linePayloads = collect($d['lines'])
            ->map(function (array $line) {
                return [
                    'item_id' => (int) $line['item_id'],
                    'qty_ordered' => (float) $line['qty_ordered'],
                    'unit_price' => (float) ($line['unit_price'] ?? 0),
                ];
            })
            ->filter(fn (array $line) => $line['item_id'] > 0 && $line['qty_ordered'] > 0)
            ->values();

        if ($linePayloads->isEmpty()) {
            return back()->withErrors(['lines' => 'At least one valid PO line is required.'])->withInput();
        }

        if ($linePayloads->pluck('item_id')->duplicates()->isNotEmpty()) {
            return back()->withErrors(['lines' => 'Same item cannot be added twice in the same PO.'])->withInput();
        }

        DB::transaction(function () use ($d, $r, $linePayloads, &$po) {
            $po = PurchaseOrder::create([
                'vendor_id' => $d['vendor_id'],
                'po_number' => 'PO-'.now()->format('YmdHis'),
                'po_date' => $d['po_date'],
                'status' => 'ISSUED',
                'remarks' => $r->input('remarks'),
            ]);

            foreach ($linePayloads as $line) {
                PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $line['item_id'],
                    'qty_ordered' => $line['qty_ordered'],
                    'unit_price' => $line['unit_price'],
                ]);
            }
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
            'purchase_order_line_id' => 'required|exists:purchase_order_lines,id',
            'item_id' => 'required|exists:items,id',
            'received_date' => 'required|date',
            'qty_received' => 'required|numeric|gt:0',
            'unit_cost' => 'required|numeric|gt:0',
            'unit_code' => 'nullable|string|max:20',
        ]);

        $po = PurchaseOrder::query()->with(['lines', 'goodsReceipts.lines'])->findOrFail($d['purchase_order_id']);
        $poLine = $po->lines->firstWhere('id', (int) $d['purchase_order_line_id']);

        if (! $poLine) {
            return back()->withErrors(['purchase_order_line_id' => 'Selected PO line is invalid.'])->withInput();
        }

        if ((int) $poLine->item_id !== (int) $d['item_id']) {
            return back()->withErrors(['item_id' => 'Selected item does not match the PO item.'])->withInput();
        }

        $lineReceivedQty = (float) $po->goodsReceipts
            ->flatMap->lines
            ->where('item_id', (int) $poLine->item_id)
            ->sum('qty_received');
        $orderedQty = (float) $poLine->qty_ordered;
        $pendingQty = max($orderedQty - $lineReceivedQty, 0);

        if ($pendingQty <= 0) {
            return back()->withErrors(['qty_received' => 'This PO line is already fully received.'])->withInput();
        }

        if ((float) $d['qty_received'] > $pendingQty) {
            return back()->withErrors(['qty_received' => 'Receive quantity cannot exceed pending quantity.'])->withInput();
        }

        DB::transaction(function () use ($d, $r, &$grn) {
            $po = PurchaseOrder::query()->with(['lines', 'goodsReceipts.lines'])->lockForUpdate()->findOrFail($d['purchase_order_id']);
            $poLine = $po->lines->firstWhere('id', (int) $d['purchase_order_line_id']);

            if (! $poLine) {
                throw new \RuntimeException('Selected PO line is invalid.');
            }

            if ((int) $poLine->item_id !== (int) $d['item_id']) {
                throw new \RuntimeException('Selected item does not match the PO item.');
            }

            $lineReceivedQty = (float) $po->goodsReceipts
                ->flatMap->lines
                ->where('item_id', (int) $poLine->item_id)
                ->sum('qty_received');
            $orderedQty = (float) $poLine->qty_ordered;
            $pendingQty = max($orderedQty - $lineReceivedQty, 0);

            if ($pendingQty <= 0) {
                throw new \RuntimeException('This PO line is already fully received.');
            }

            if ((float) $d['qty_received'] > $pendingQty) {
                throw new \RuntimeException('Receive quantity cannot exceed pending quantity.');
            }

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
                'unit_cost' => $d['unit_cost'],
            ]);

            $item = Item::query()->findOrFail($d['item_id']);
            $unitCode = $d['unit_code'] ?? null;
            $transQty = (float) $d['qty_received'];
            $baseQty = $transQty;
            $transUnitCode = null;
            $transQuantity = null;

            if ($unitCode !== null && $unitCode !== '') {
                $unit = $item->units()->where('unit_code', $unitCode)->first();
                if (! $unit) {
                    throw new \RuntimeException('Invalid unit for item');
                }

                $baseQty = $transQty * (float) $unit->factor_to_base;
                $transUnitCode = $unit->unit_code;
                $transQuantity = $transQty;
            }

            StockTransaction::create([
                'item_id' => $d['item_id'],
                'txn_type' => 'GRN',
                'quantity' => $baseQty,
                'unit_cost' => $d['unit_cost'],
                'trans_unit_code' => $transUnitCode,
                'trans_quantity' => $transQuantity,
                'reference_type' => GoodsReceipt::class,
                'reference_id' => $grn->id,
                'txn_at' => $d['received_date'],
                'remarks' => 'GRN posting (stock posted on create)',
            ]);

            $po->load(['lines', 'goodsReceipts.lines']);
            $totalOrderedQty = (float) $po->lines->sum('qty_ordered');
            $totalReceivedQty = (float) $po->goodsReceipts->flatMap->lines->sum('qty_received');

            PurchaseOrder::whereKey($d['purchase_order_id'])->update([
                'status' => $totalReceivedQty < $totalOrderedQty ? 'PARTIALLY_RECEIVED' : 'RECEIVED',
            ]);
        });

        return back()->with('success', 'GRN posted');
    }

    public function approveGrn(GoodsReceipt $grn): RedirectResponse
    {
        $po = PurchaseOrder::query()->with(['lines', 'goodsReceipts.lines'])->findOrFail($grn->purchase_order_id);
        $totalOrderedQty = (float) $po->lines->sum('qty_ordered');
        $totalReceivedQty = (float) $po->goodsReceipts->flatMap->lines->sum('qty_received');

        PurchaseOrder::whereKey($grn->purchase_order_id)->update([
            'status' => $totalReceivedQty < $totalOrderedQty ? 'PARTIALLY_RECEIVED' : 'RECEIVED',
        ]);
        $grn->touch();

        return back()->with('success', 'GRN approval acknowledged. Stock was already posted on GRN create; no extra approval side-effect exists in current schema.');
    }
}
