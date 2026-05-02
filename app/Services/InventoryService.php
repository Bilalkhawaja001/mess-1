<?php

namespace App\Services;

use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\StockTransaction;
use Illuminate\Support\Collection;

class InventoryService
{
    public function balanceForItem(int $itemId): float
    {
        return (float) ($this->balancesForItems([$itemId])[$itemId] ?? 0);
    }

    public function balancesForItems(array $itemIds): array
    {
        $itemIds = collect($itemIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($itemIds->isEmpty()) {
            return [];
        }

        return StockTransaction::query()
            ->selectRaw("item_id, ROUND(SUM(CASE WHEN txn_type IN ('OPENING', 'IN', 'ADJUSTMENT', 'GRN') THEN quantity ELSE -quantity END), 3) as balance")
            ->whereIn('item_id', $itemIds)
            ->groupBy('item_id')
            ->pluck('balance', 'item_id')
            ->map(fn ($balance) => (float) $balance)
            ->all();
    }

    public function movementTotalsForItems(array $itemIds): array
    {
        $itemIds = collect($itemIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($itemIds->isEmpty()) {
            return [];
        }

        return StockTransaction::query()
            ->selectRaw("item_id,
                ROUND(SUM(CASE WHEN txn_type IN ('OPENING', 'IN', 'ADJUSTMENT', 'GRN') THEN quantity ELSE 0 END), 3) as received_qty,
                ROUND(SUM(CASE WHEN txn_type IN ('OUT', 'KITCHEN_ISSUE', 'VENDOR_RETURN') THEN quantity ELSE 0 END), 3) as issued_qty")
            ->whereIn('item_id', $itemIds)
            ->groupBy('item_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->item_id => [
                    'received_qty' => (float) $row->received_qty,
                    'issued_qty' => (float) $row->issued_qty,
                ],
            ])
            ->all();
    }

    public function stockBalances(): array
    {
        $items = Item::query()->get();
        $balances = $this->balancesForItems($items->pluck('id')->all());

        return $items
            ->map(fn (Item $i) => ['item' => $i, 'balance' => (float) ($balances[$i->id] ?? 0)])
            ->all();
    }

    public function lowStockItems(): array
    {
        $items = Item::query()
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->get();
        $balances = $this->balancesForItems($items->pluck('id')->all());

        return $items
            ->map(function (Item $item) use ($balances) {
                $balance = (float) ($balances[$item->id] ?? 0);

                return [
                    'item' => $item,
                    'balance' => $balance,
                    'is_low' => $balance <= (float) $item->reorder_level,
                ];
            })
            ->filter(fn (array $row) => $row['is_low'])
            ->values()
            ->all();
    }

    public function procurementToConsumptionTrail(int $itemId): array
    {
        $inward = StockTransaction::query()
            ->where('item_id', $itemId)
            ->whereIn('txn_type', ['GRN', 'OPENING', 'IN'])
            ->orderBy('txn_at', 'desc')
            ->limit(200)
            ->get()
            ->map(function (StockTransaction $txn) {
                $grnNumber = null;
                $poNumber = null;
                $vendorName = null;

                if ($txn->reference_id) {
                    if ($txn->reference_type === GoodsReceiptLine::class) {
                        $grnLine = GoodsReceiptLine::query()
                            ->with(['goodsReceipt.purchaseOrder.vendor'])
                            ->find($txn->reference_id);
                        if ($grnLine?->goodsReceipt) {
                            $grnNumber = $grnLine->goodsReceipt->grn_number;
                            $poNumber = $grnLine->goodsReceipt->purchaseOrder?->po_number;
                            $vendorName = $grnLine->goodsReceipt->purchaseOrder?->vendor?->name;
                        }
                    } elseif ($txn->reference_type === \App\Models\GoodsReceipt::class) {
                        $grn = \App\Models\GoodsReceipt::query()
                            ->with(['purchaseOrder.vendor'])
                            ->find($txn->reference_id);
                        if ($grn) {
                            $grnNumber = $grn->grn_number;
                            $poNumber = $grn->purchaseOrder?->po_number;
                            $vendorName = $grn->purchaseOrder?->vendor?->name;
                        }
                    }
                }

                return [
                    'txn_at' => $txn->txn_at,
                    'quantity' => $txn->quantity,
                    'trans_unit_code' => $txn->trans_unit_code,
                    'trans_quantity' => $txn->trans_quantity,
                    'unit_cost' => $txn->unit_cost,
                    'grn_number' => $grnNumber,
                    'po_number' => $poNumber,
                    'vendor_name' => $vendorName,
                ];
            })
            ->all();

        $outward = StockTransaction::query()
            ->where('item_id', $itemId)
            ->whereIn('txn_type', ['KITCHEN_ISSUE', 'OUT', 'VENDOR_RETURN'])
            ->orderBy('txn_at', 'desc')
            ->limit(200)
            ->get()
            ->map(function (StockTransaction $txn) {
                $issueType = null;
                $messName = null;
                $remarks = $txn->remarks;

                if ($txn->reference_type === \App\Models\KitchenIssue::class && $txn->reference_id) {
                    $issue = \App\Models\KitchenIssue::query()->with('mess')->find($txn->reference_id);
                    if ($issue) {
                        $issueType = $issue->issue_type;
                        $messName = $issue->mess?->name;
                        $remarks = $issue->remarks;
                    }
                }

                if ($txn->reference_type === \App\Models\VendorReturn::class && $txn->reference_id) {
                    $vendorReturn = \App\Models\VendorReturn::query()->with('vendor')->find($txn->reference_id);
                    if ($vendorReturn) {
                        $issueType = 'VENDOR_RETURN';
                        $messName = $vendorReturn->vendor?->name;
                        $remarks = $vendorReturn->remarks;
                    }
                }

                return [
                    'txn_at' => $txn->txn_at,
                    'quantity' => $txn->quantity,
                    'trans_unit_code' => $txn->trans_unit_code,
                    'trans_quantity' => $txn->trans_quantity,
                    'issue_type' => $issueType,
                    'mess_name' => $messName,
                    'remarks' => $remarks,
                ];
            })
            ->all();

        return [
            'inward' => $inward,
            'outward' => $outward,
        ];
    }

    public function currentUnitCostForItem(int $itemId): float
    {
        $latestInboundCost = StockTransaction::query()
            ->where('item_id', $itemId)
            ->whereIn('txn_type', ['OPENING', 'IN', 'ADJUSTMENT', 'GRN'])
            ->where('unit_cost', '>', 0)
            ->orderByDesc('txn_at')
            ->orderByDesc('id')
            ->value('unit_cost');

        return round((float) ($latestInboundCost ?? 0), 2);
    }
}
