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
        $items = Item::query()
            ->where('is_active', true)
            ->with('units')
            ->orderBy('sku')
            ->get();
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
        $grnEligiblePos = $pos
            ->filter(fn (PurchaseOrder $po) => $po->lines->contains(fn ($line) => (float) ($line->pending_qty ?? 0) > 0))
            ->values();
        $grns = GoodsReceipt::query()->with(['purchaseOrder.vendor', 'lines.item'])->latest()->limit(50)->get();

        return view('admin.procurement.index', compact('vendors', 'items', 'pos', 'grnEligiblePos', 'grns'));
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
        $this->approvePurchaseOrderRecord($po);

        return back()->with('success', 'PO approved. Current schema has no deeper approval posting beyond status transition.');
    }

    public function bulkApprovePo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'po_ids' => 'required|array|min:1',
            'po_ids.*' => 'integer|exists:purchase_orders,id',
        ]);

        $pos = PurchaseOrder::query()->whereIn('id', $data['po_ids'])->get();

        foreach ($pos as $po) {
            $this->approvePurchaseOrderRecord($po);
        }

        return back()->with('success', $pos->count().' purchase order(s) approved.');
    }

    public function storeGrn(Request $r): RedirectResponse
    {
        $d = $r->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'received_date' => 'required|date',
            'receive_rows' => 'required|array|min:1',
            'receive_rows.*' => 'nullable|array',
            'receive_rows.*.selected' => 'nullable|boolean',
            'receive_rows.*.purchase_order_line_id' => 'required_with:receive_rows.*.selected|exists:purchase_order_lines,id',
            'receive_rows.*.item_id' => 'required_with:receive_rows.*.selected|exists:items,id',
            'receive_rows.*.qty_received' => 'nullable|numeric|gt:0',
            'receive_rows.*.unit_cost' => 'nullable|numeric|gt:0',
            'receive_rows.*.unit_code' => 'nullable|string|max:20',
            'receive_rows.*.override_po_rate' => 'nullable|boolean',
            'receive_rows.*.override_reason' => 'nullable|string|max:500',
            'receive_rows.*.remarks' => 'nullable|string|max:1000',
        ]);

        $selectedRows = collect($d['receive_rows'] ?? [])->filter(fn (array $row) => (bool) ($row['selected'] ?? false))->values();

        if ($selectedRows->isEmpty()) {
            return back()->withErrors(['receive_rows' => 'Select at least one PO row to receive.'])->withInput();
        }

        $po = PurchaseOrder::query()->with(['lines.item.units', 'goodsReceipts.lines'])->findOrFail($d['purchase_order_id']);

        try {
            $validatedRows = $this->buildValidatedGrnRows($po, $selectedRows);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['receive_rows' => $e->getMessage()])->withInput();
        }

        DB::transaction(function () use ($d, $po, $validatedRows, &$grn) {
            $lockedPo = PurchaseOrder::query()->with(['lines.item.units', 'goodsReceipts.lines'])->lockForUpdate()->findOrFail($po->id);
            $lockedRows = $this->buildValidatedGrnRows($lockedPo, $validatedRows->map(fn (array $row) => $row['request'])->values());

            $grn = GoodsReceipt::create([
                'purchase_order_id' => $lockedPo->id,
                'grn_number' => 'GRN-'.now()->format('YmdHis'),
                'received_date' => $d['received_date'],
                'remarks' => $lockedRows->pluck('request.remarks')->filter()->implode(' | ') ?: null,
            ]);

            foreach ($lockedRows as $row) {
                $meta = $row['meta'];
                $requestRow = $row['request'];

                GoodsReceiptLine::create([
                    'goods_receipt_id' => $grn->id,
                    'item_id' => $meta['item']->id,
                    'qty_received' => $meta['qty_received'],
                    'unit_cost' => $meta['unit_cost'],
                ]);

                $baseQty = $meta['qty_received'] * (float) $meta['unit']->factor_to_base;

                StockTransaction::create([
                    'item_id' => $meta['item']->id,
                    'txn_type' => 'GRN',
                    'quantity' => $baseQty,
                    'unit_cost' => $meta['unit_cost'],
                    'trans_unit_code' => $meta['unit']->unit_code,
                    'trans_quantity' => $meta['qty_received'],
                    'reference_type' => GoodsReceipt::class,
                    'reference_id' => $grn->id,
                    'txn_at' => $d['received_date'],
                    'remarks' => trim(implode(' | ', array_filter([
                        'GRN posting (stock posted on create)',
                        $requestRow['remarks'] ?? null,
                        $meta['override_note'] ?? null,
                    ]))),
                ]);
            }

            $lockedPo->load(['lines', 'goodsReceipts.lines']);
            $totalOrderedQty = (float) $lockedPo->lines->sum('qty_ordered');
            $totalReceivedQty = (float) $lockedPo->goodsReceipts->flatMap->lines->sum('qty_received');

            PurchaseOrder::whereKey($lockedPo->id)->update([
                'status' => $totalReceivedQty < $totalOrderedQty ? 'PARTIALLY_RECEIVED' : 'RECEIVED',
            ]);
        });

        return back()->with('success', 'GRN posted for selected PO rows.');
    }

    public function approveGrn(GoodsReceipt $grn): RedirectResponse
    {
        $this->acknowledgeGoodsReceiptRecord($grn);

        return back()->with('success', 'GRN approval acknowledged. Stock was already posted on GRN create; no extra approval side-effect exists in current schema.');
    }

    public function bulkApproveGrn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'grn_ids' => 'required|array|min:1',
            'grn_ids.*' => 'integer|exists:goods_receipts,id',
        ]);

        $grns = GoodsReceipt::query()->whereIn('id', $data['grn_ids'])->get();

        foreach ($grns as $grn) {
            $this->acknowledgeGoodsReceiptRecord($grn);
        }

        return back()->with('success', $grns->count().' GRN acknowledgement(s) processed.');
    }

    private function approvePurchaseOrderRecord(PurchaseOrder $po): void
    {
        $po->status = 'APPROVED';
        $po->save();
    }

    private function buildValidatedGrnRows(PurchaseOrder $po, $selectedRows)
    {
        $selectedRows = collect($selectedRows)->values();

        if ($selectedRows->isEmpty()) {
            throw new \RuntimeException('Select at least one PO row to receive.');
        }

        $poHasPendingLines = $po->lines->contains(function (PurchaseOrderLine $line) use ($po): bool {
            $receivedQty = (float) $po->goodsReceipts
                ->flatMap->lines
                ->where('item_id', (int) $line->item_id)
                ->sum('qty_received');

            return max((float) $line->qty_ordered - $receivedQty, 0) > 0;
        });

        if (! $poHasPendingLines) {
            throw new \RuntimeException('Selected PO is already fully received.');
        }

        return $selectedRows->map(function (array $row) use ($po) {
            $poLine = $po->lines->firstWhere('id', (int) ($row['purchase_order_line_id'] ?? 0));
            if (! $poLine) {
                throw new \RuntimeException('Selected PO line is invalid.');
            }

            if ((int) $poLine->item_id !== (int) ($row['item_id'] ?? 0)) {
                throw new \RuntimeException('Selected item does not match the PO item.');
            }

            $lineReceivedQty = (float) $po->goodsReceipts
                ->flatMap->lines
                ->where('item_id', (int) $poLine->item_id)
                ->sum('qty_received');
            $orderedQty = (float) $poLine->qty_ordered;
            $pendingQty = max($orderedQty - $lineReceivedQty, 0);

            if ($pendingQty <= 0) {
                throw new \RuntimeException('One selected PO row is already fully received.');
            }

            $qtyReceived = (float) ($row['qty_received'] ?? 0);
            if ($qtyReceived <= 0) {
                throw new \RuntimeException('Received quantity must be greater than zero for each selected row.');
            }

            if ($qtyReceived > $pendingQty) {
                throw new \RuntimeException('Receive quantity cannot exceed pending quantity.');
            }

            $item = Item::query()->with('units')->findOrFail((int) $row['item_id']);
            $unitCode = trim((string) ($row['unit_code'] ?? ''));
            $unit = $item->units->firstWhere('unit_code', $unitCode);
            if (! $unit) {
                throw new \RuntimeException('Invalid unit for selected item.');
            }

            $poUnitPrice = round((float) $poLine->unit_price, 2);
            $unitCost = round((float) ($row['unit_cost'] ?? 0), 2);
            $overridePoRate = (bool) ($row['override_po_rate'] ?? false);

            if ($unitCost <= 0) {
                throw new \RuntimeException('Unit cost must be greater than zero for each selected row.');
            }

            if (! $overridePoRate && $unitCost !== $poUnitPrice) {
                throw new \RuntimeException('GRN unit cost must match the selected PO line rate unless override is enabled.');
            }

            if ($overridePoRate && blank($row['override_reason'] ?? null)) {
                throw new \RuntimeException('Override reason is required when PO rate override is enabled.');
            }

            $overrideNote = $overridePoRate
                ? 'GRN rate override. PO rate: '.number_format($poUnitPrice, 2, '.', '').'; Actual GRN rate: '.number_format($unitCost, 2, '.', '').'; Reason: '.trim((string) ($row['override_reason'] ?? ''))
                : null;

            return [
                'request' => $row,
                'meta' => [
                    'po_line' => $poLine,
                    'item' => $item,
                    'unit' => $unit,
                    'qty_received' => $qtyReceived,
                    'unit_cost' => $unitCost,
                    'pending_qty' => $pendingQty,
                    'override_note' => $overrideNote,
                ],
            ];
        });
    }

    private function acknowledgeGoodsReceiptRecord(GoodsReceipt $grn): void
    {
        $po = PurchaseOrder::query()->with(['lines', 'goodsReceipts.lines'])->findOrFail($grn->purchase_order_id);
        $totalOrderedQty = (float) $po->lines->sum('qty_ordered');
        $totalReceivedQty = (float) $po->goodsReceipts->flatMap->lines->sum('qty_received');

        PurchaseOrder::whereKey($grn->purchase_order_id)->update([
            'status' => $totalReceivedQty < $totalOrderedQty ? 'PARTIALLY_RECEIVED' : 'RECEIVED',
        ]);
        $grn->touch();
    }
}
