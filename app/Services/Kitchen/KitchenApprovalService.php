<?php

namespace App\Services\Kitchen;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\ItemUnit;
use App\Models\KitchenGrn;
use App\Models\KitchenPo;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockTransaction;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\DB;

class KitchenApprovalService
{
    /**
     * @param  array<int, array{item_id:int, unit_price:float|int|string}>  $rates
     *
     * @throws \RuntimeException
     */
    public function approvePo(int $kitchenPoId, int $userId, array $rates = []): PurchaseOrder
    {
        $poId = DB::transaction(function () use ($kitchenPoId, $userId, $rates) {
            $kpo = KitchenPo::with('lines')->lockForUpdate()->find($kitchenPoId);

            if (! $kpo) {
                throw new \RuntimeException('Purchase order not found', 404);
            }
            if ($kpo->status !== KitchenPo::STATUS_PENDING) {
                throw new \RuntimeException('Already '.strtolower($kpo->status), 422);
            }

            $rateMap = collect($rates)->keyBy(fn ($r) => (int) $r['item_id']);

            $po = PurchaseOrder::create([
                'vendor_id' => $kpo->vendor_id,
                'po_number' => DocumentNumber::generate('PO'),
                'po_date' => $kpo->po_date,
                'status' => 'DRAFT',
                'remarks' => $kpo->remarks,
                'created_by_staff_id' => $kpo->kitchen_staff_id,
                'approved_by_user_id' => $userId,
                'approved_at' => now(),
            ]);

            foreach ($kpo->lines as $line) {
                $itemId = (int) $line->item_id;

                $poLine = PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $itemId,
                    'qty_ordered' => (float) $line->qty_ordered,
                    'unit_price' => $rateMap->has($itemId) ? (float) $rateMap[$itemId]['unit_price'] : 0,
                ]);

                $line->forceFill(['unit_price' => $poLine->unit_price])->save();
            }

            $kpo->forceFill([
                'status' => KitchenPo::STATUS_APPROVED,
                'purchase_order_id' => $po->id,
                'reviewed_by_user_id' => $userId,
                'reviewed_at' => now(),
            ])->save();

            KitchenGrn::where('kitchen_po_id', $kpo->id)
                ->whereNull('purchase_order_id')
                ->update(['purchase_order_id' => $po->id]);

            return $po->id;
        });

        return PurchaseOrder::findOrFail($poId);
    }

    public function rejectPo(int $kitchenPoId, int $userId, string $reason): void
    {
        $kpo = KitchenPo::where('status', KitchenPo::STATUS_PENDING)->find($kitchenPoId);

        if (! $kpo) {
            throw new \RuntimeException('Pending purchase order not found', 404);
        }

        DB::transaction(function () use ($kpo, $userId, $reason) {
            $kpo->forceFill([
                'status' => KitchenPo::STATUS_REJECTED,
                'reject_reason' => $reason,
                'reviewed_by_user_id' => $userId,
                'reviewed_at' => now(),
            ])->save();

            KitchenGrn::where('kitchen_po_id', $kpo->id)
                ->where('status', KitchenGrn::STATUS_PENDING)
                ->update([
                    'status' => KitchenGrn::STATUS_REJECTED,
                    'reject_reason' => 'Parent purchase order rejected',
                    'reviewed_by_user_id' => $userId,
                    'reviewed_at' => now(),
                ]);
        });
    }

    /**
     * @param  array<int, array{item_id:int, unit_cost:float|int|string}>  $costs
     *
     * @throws \RuntimeException
     */
    public function approveGrn(int $kitchenGrnId, int $userId, array $costs): string
    {
        return DB::transaction(function () use ($kitchenGrnId, $userId, $costs) {
            $kgrn = KitchenGrn::with(['lines', 'kitchenPo'])->lockForUpdate()->find($kitchenGrnId);

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

            $costMap = collect($costs)->keyBy(fn ($c) => (int) $c['item_id']);

            $grn = GoodsReceipt::create([
                'purchase_order_id' => $po->id,
                'grn_number' => DocumentNumber::generate('GRN'),
                'received_date' => $kgrn->received_date,
                'remarks' => $kgrn->remarks,
                'created_by_staff_id' => $kgrn->kitchen_staff_id,
                'approved_by_user_id' => $userId,
                'approved_at' => now(),
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

                if (! $costMap->has($itemId)) {
                    throw new \RuntimeException('Unit cost is required for every item', 422);
                }

                $unitCost = (float) $costMap[$itemId]['unit_cost'];

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
                'reviewed_by_user_id' => $userId,
                'reviewed_at' => now(),
            ])->save();

            return $grn->grn_number;
        });
    }

    public function rejectGrn(int $kitchenGrnId, int $userId, string $reason): void
    {
        $kgrn = KitchenGrn::where('status', KitchenGrn::STATUS_PENDING)->find($kitchenGrnId);

        if (! $kgrn) {
            throw new \RuntimeException('Pending GRN not found', 404);
        }

        $kgrn->forceFill([
            'status' => KitchenGrn::STATUS_REJECTED,
            'reject_reason' => $reason,
            'reviewed_by_user_id' => $userId,
            'reviewed_at' => now(),
        ])->save();
    }
}
