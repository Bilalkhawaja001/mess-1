<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockTransaction;

class InventoryService
{
    public function balanceForItem(int $itemId): float
    {
        $in = (float) StockTransaction::query()
            ->where('item_id', $itemId)
            ->whereIn('txn_type', ['OPENING', 'IN', 'ADJUSTMENT', 'GRN'])
            ->sum('quantity');

        $out = (float) StockTransaction::query()
            ->where('item_id', $itemId)
            ->whereIn('txn_type', ['OUT', 'KITCHEN_ISSUE', 'VENDOR_RETURN'])
            ->sum('quantity');

        return round($in - $out, 3);
    }

    public function stockBalances(): array
    {
        return Item::query()
            ->get()
            ->map(fn (Item $i) => ['item' => $i, 'balance' => $this->balanceForItem($i->id)])
            ->all();
    }

    public function lowStockItems(): array
    {
        return Item::query()
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->get()
            ->map(function (Item $item) {
                $balance = $this->balanceForItem($item->id);

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

                if ($txn->reference_type === \App\Models\GoodsReceipt::class && $txn->reference_id) {
                    $grn = \App\Models\GoodsReceipt::query()
                        ->with('purchaseOrder.vendor')
                        ->find($txn->reference_id);
                    if ($grn) {
                        $grnNumber = $grn->grn_number;
                        $poNumber = $grn->purchaseOrder?->po_number;
                        $vendorName = $grn->purchaseOrder?->vendor?->name;
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
