<?php

namespace App\Http\Controllers\Api\AdminApp;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\ItemUnit;
use App\Models\KitchenGrn;
use App\Models\KitchenGrnLine;
use App\Models\KitchenPo;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockTransaction;
use App\Support\DocumentNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminProcurementController extends AdminAuthController
{
    protected const PERM = 'procurement.manage';

    public function pending(Request $request): JsonResponse
    {
        if (! $this->requirePermission($request, self::PERM)) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $pos = KitchenPo::with(['staff:id,name,staff_code', 'vendor:id,name', 'lines.item:id,name,sku,uom'])
            ->where('status', KitchenPo::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        $grns = KitchenGrn::with(['staff:id,name,staff_code', 'kitchenPo:id,temp_number,status', 'lines.item:id,name,sku,uom'])
            ->where('status', KitchenGrn::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'purchase_orders' => $pos->map(fn ($po) => [
                'id' => (int) $po->id,
                'temp_number' => $po->temp_number,
                'staff' => $po->staff->name ?? null,
                'vendor' => $po->vendor->name ?? null,
                'po_date' => optional($po->po_date)->format('Y-m-d'),
                'remarks' => $po->remarks,
                'lines' => $po->lines->map(fn ($l) => [
                    'id' => (int) $l->id,
                    'item_id' => (int) $l->item_id,
                    'item' => $l->item->name ?? null,
                    'uom' => $l->item->uom ?? null,
                    'qty_ordered' => (string) $l->qty_ordered,
                ]),
            ]),
            'goods_receipts' => $grns->map(fn ($g) => [
                'id' => (int) $g->id,
                'temp_number' => $g->temp_number,
                'staff' => $g->staff->name ?? null,
                'po_temp_number' => $g->kitchenPo->temp_number ?? null,
                'po_status' => $g->kitchenPo->status ?? null,
                'received_date' => optional($g->received_date)->format('Y-m-d'),
                'lines' => $g->lines->map(fn ($l) => [
                    'line_id' => (int) $l->id,
                    'has_image' => (bool) $l->image_path,
                    'item_id' => (int) $l->item_id,
                    'item' => $l->item->name ?? null,
                    'uom' => $l->item->uom ?? null,
                    'qty_received' => (string) $l->qty_received,
                ]),
            ]),
        ]);
    }

    public function grnLineImage(Request $request, int $lineId)
    {
        if (! $this->requirePermission($request, self::PERM)) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $line = KitchenGrnLine::find($lineId);
        if (! $line) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        $path = (string) $line->image_path;
        $disk = (string) ($line->image_disk ?: 'local');

        if ($path === '' || ! in_array($disk, ['local', 'public'], true) || ! Storage::disk($disk)->exists($path)) {
            return response()->json(['success' => false, 'message' => 'Image not available'], 404);
        }

        return response()->file(Storage::disk($disk)->path($path), [
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approvePo(Request $request, int $id): JsonResponse
    {
        $user = $this->requirePermission($request, self::PERM);
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $data = $request->validate([
            'rates' => ['nullable', 'array'],
            'rates.*.item_id' => ['required_with:rates', 'integer'],
            'rates.*.unit_price' => ['required_with:rates', 'numeric', 'gte:0'],
        ]);

        try {
            $poId = DB::transaction(function () use ($id, $data, $user) {
                $kpo = KitchenPo::with('lines')->lockForUpdate()->find($id);

                if (! $kpo) {
                    throw new \RuntimeException('Purchase order not found', 404);
                }
                if ($kpo->status !== KitchenPo::STATUS_PENDING) {
                    throw new \RuntimeException('Already '.strtolower($kpo->status), 422);
                }

                $rates = collect($data['rates'] ?? [])->keyBy(fn ($r) => (int) $r['item_id']);

                $po = PurchaseOrder::create([
                    'vendor_id' => $kpo->vendor_id,
                    'po_number' => DocumentNumber::generate('PO'),
                    'po_date' => $kpo->po_date,
                    'status' => 'DRAFT',
                    'remarks' => $kpo->remarks,
                ]);

                foreach ($kpo->lines as $line) {
                    $poLine = PurchaseOrderLine::create([
                        'purchase_order_id' => $po->id,
                        'item_id' => (int) $line->item_id,
                        'qty_ordered' => (float) $line->qty_ordered,
                        'unit_price' => $rates->has((int) $line->item_id)
                            ? (float) $rates[(int) $line->item_id]['unit_price']
                            : 0,
                    ]);
                    $line->forceFill(['unit_price' => $poLine->unit_price])->save();
                }

                $kpo->forceFill([
                    'status' => KitchenPo::STATUS_APPROVED,
                    'purchase_order_id' => $po->id,
                    'reviewed_by_user_id' => $user->id,
                    'reviewed_at' => now(),
                ])->save();

                KitchenGrn::where('kitchen_po_id', $kpo->id)
                    ->whereNull('purchase_order_id')
                    ->update(['purchase_order_id' => $po->id]);

                return $po->id;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        $po = PurchaseOrder::find($poId);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order approved',
            'po_number' => $po->po_number,
        ]);
    }

    public function rejectPo(Request $request, int $id): JsonResponse
    {
        $user = $this->requirePermission($request, self::PERM);
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $kpo = KitchenPo::where('status', KitchenPo::STATUS_PENDING)->find($id);
        if (! $kpo) {
            return response()->json(['success' => false, 'message' => 'Pending purchase order not found'], 404);
        }

        DB::transaction(function () use ($kpo, $data, $user) {
            $kpo->forceFill([
                'status' => KitchenPo::STATUS_REJECTED,
                'reject_reason' => $data['reason'],
                'reviewed_by_user_id' => $user->id,
                'reviewed_at' => now(),
            ])->save();

            KitchenGrn::where('kitchen_po_id', $kpo->id)
                ->where('status', KitchenGrn::STATUS_PENDING)
                ->update([
                    'status' => KitchenGrn::STATUS_REJECTED,
                    'reject_reason' => 'Parent purchase order rejected',
                    'reviewed_by_user_id' => $user->id,
                    'reviewed_at' => now(),
                ]);
        });

        return response()->json(['success' => true, 'message' => 'Purchase order rejected']);
    }

    public function approveGrn(Request $request, int $id): JsonResponse
    {
        $user = $this->requirePermission($request, self::PERM);
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $data = $request->validate([
            'costs' => ['required', 'array', 'min:1'],
            'costs.*.item_id' => ['required', 'integer'],
            'costs.*.unit_cost' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $grnNumber = DB::transaction(function () use ($id, $data, $user) {
                $kgrn = KitchenGrn::with(['lines', 'kitchenPo'])->lockForUpdate()->find($id);

                if (! $kgrn) {
                    throw new \RuntimeException('GRN not found', 404);
                }
                if ($kgrn->status !== KitchenGrn::STATUS_PENDING) {
                    throw new \RuntimeException('Already '.strtolower($kgrn->status), 422);
                }
                if (! $kgrn->purchase_order_id) {
                    throw new \RuntimeException('Approve the purchase order first', 422);
                }

                $po = PurchaseOrder::with(['lines', 'goodsReceipts.lines'])
                    ->lockForUpdate()
                    ->findOrFail($kgrn->purchase_order_id);

                $overrides = collect($data['costs'] ?? [])->keyBy(fn ($c) => (int) $c['item_id']);

                $grn = GoodsReceipt::create([
                    'purchase_order_id' => $po->id,
                    'grn_number' => DocumentNumber::generate('GRN'),
                    'received_date' => $kgrn->received_date,
                    'remarks' => $kgrn->remarks,
                ]);

                foreach ($kgrn->lines as $line) {
                    $itemId = (int) $line->item_id;

                    $poLine = $po->lines->firstWhere('item_id', $itemId);
                    if (! $poLine) {
                        throw new \RuntimeException('Item not on the purchase order', 422);
                    }

                    $alreadyReceived = (float) $po->goodsReceipts
                        ->flatMap->lines
                        ->where('item_id', $itemId)
                        ->sum('qty_received');

                    if ($alreadyReceived + (float) $line->qty_received > (float) $poLine->qty_ordered) {
                        throw new \RuntimeException('Received quantity exceeds ordered quantity', 422);
                    }

                    if (! $overrides->has($itemId)) {
                        throw new \RuntimeException('Unit cost is required for every item', 422);
                    }

                    $unitCost = (float) $overrides[$itemId]['unit_cost'];

                    $unit = ItemUnit::where('item_id', $itemId)
                        ->orderByDesc('is_default_for_grn')
                        ->orderBy('id')
                        ->first();

                    if (! $unit) {
                        throw new \RuntimeException('Item unit not configured', 422);
                    }

                    $grnLine = GoodsReceiptLine::create([
                        'goods_receipt_id' => $grn->id,
                        'purchase_order_line_id' => $poLine->id,
                        'item_id' => $itemId,
                        'qty_received' => (float) $line->qty_received,
                        'unit_cost' => $unitCost,
                    ]);

                    $line->forceFill(['unit_cost' => $unitCost])->save();

                    if ((float) $poLine->unit_price <= 0) {
                        $poLine->forceFill(['unit_price' => $unitCost])->save();
                    }

                    StockTransaction::create([
                        'item_id' => $itemId,
                        'txn_type' => 'GRN',
                        'quantity' => (float) $line->qty_received * (float) $unit->factor_to_base,
                        'unit_cost' => $unitCost,
                        'trans_unit_code' => $unit->unit_code,
                        'trans_quantity' => (float) $line->qty_received,
                        'reference_type' => GoodsReceiptLine::class,
                        'reference_id' => $grnLine->id,
                        'txn_at' => $kgrn->received_date,
                        'remarks' => 'GRN posting (kitchen staff '.$kgrn->temp_number.')',
                    ]);
                }

                $po->load(['lines', 'goodsReceipts.lines']);
                $ordered = (float) $po->lines->sum('qty_ordered');
                $received = (float) $po->goodsReceipts->flatMap->lines->sum('qty_received');

                PurchaseOrder::whereKey($po->id)->update([
                    'status' => $received < $ordered ? 'PARTIALLY_RECEIVED' : 'RECEIVED',
                ]);

                $kgrn->forceFill([
                    'status' => KitchenGrn::STATUS_APPROVED,
                    'goods_receipt_id' => $grn->id,
                    'reviewed_by_user_id' => $user->id,
                    'reviewed_at' => now(),
                ])->save();

                return $grn->grn_number;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'GRN approved and stock posted',
            'grn_number' => $grnNumber,
        ]);
    }

    public function rejectGrn(Request $request, int $id): JsonResponse
    {
        $user = $this->requirePermission($request, self::PERM);
        if (! $user) {
            return $this->user($request) ? $this->forbidden() : $this->unauthenticated();
        }

        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $kgrn = KitchenGrn::where('status', KitchenGrn::STATUS_PENDING)->find($id);
        if (! $kgrn) {
            return response()->json(['success' => false, 'message' => 'Pending GRN not found'], 404);
        }

        $kgrn->forceFill([
            'status' => KitchenGrn::STATUS_REJECTED,
            'reject_reason' => $data['reason'],
            'reviewed_by_user_id' => $user->id,
            'reviewed_at' => now(),
        ])->save();

        return response()->json(['success' => true, 'message' => 'GRN rejected']);
    }
}
