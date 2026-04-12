<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KitchenIssueReportService
{
    public function build(string $fromDate, string $toDate, ?int $itemId = null): array
    {
        $issueRows = DB::table('kitchen_issues')
            ->join('items', 'items.id', '=', 'kitchen_issues.item_id')
            ->where('kitchen_issues.status', 'approved')
            ->whereBetween('kitchen_issues.issue_date', [$fromDate, $toDate])
            ->when($itemId, fn ($query) => $query->where('kitchen_issues.item_id', $itemId))
            ->groupBy('kitchen_issues.item_id', 'items.sku', 'items.name', 'items.uom')
            ->orderBy('items.name')
            ->get([
                'kitchen_issues.item_id',
                'items.sku',
                'items.name',
                'items.uom',
                DB::raw('SUM(kitchen_issues.quantity) as total_issued_qty'),
                DB::raw('COUNT(*) as issue_count'),
            ]);

        $rates = $this->weightedInboundRates(
            $issueRows->pluck('item_id')->map(fn ($id) => (int) $id)->all(),
            $toDate
        );

        $rows = $issueRows->map(function ($row) use ($rates) {
            $avgRate = (float) ($rates[$row->item_id] ?? 0);
            $totalIssuedQty = (float) $row->total_issued_qty;
            $estimatedAmount = round($totalIssuedQty * $avgRate, 2);

            return [
                'item_id' => (int) $row->item_id,
                'sku' => $row->sku,
                'item_name' => $row->name,
                'uom' => $row->uom,
                'total_issued_qty' => round($totalIssuedQty, 3),
                'issue_count' => (int) $row->issue_count,
                'avg_rate' => round($avgRate, 2),
                'estimated_amount' => $estimatedAmount,
                'has_rate_history' => $avgRate > 0,
            ];
        })->values();

        return [
            'rows' => $rows,
            'totals' => [
                'grand_total_amount' => round($rows->sum('estimated_amount'), 2),
                'total_unique_items' => $rows->count(),
                'total_approved_issue_rows' => (int) $rows->sum('issue_count'),
            ],
            'rate_source' => 'stock_transactions.unit_cost from GRN inbound postings linked to goods receipts',
            'rate_formula' => 'avg_rate = sum(inbound_qty * unit_cost) / sum(inbound_qty); estimated_amount = total_issued_qty * avg_rate',
        ];
    }

    public function itemOptions(): Collection
    {
        return Item::query()->orderBy('name')->get(['id', 'sku', 'name', 'uom']);
    }

    private function weightedInboundRates(array $itemIds, string $toDate): array
    {
        if ($itemIds === []) {
            return [];
        }

        return StockTransaction::query()
            ->whereIn('item_id', $itemIds)
            ->where('txn_type', 'GRN')
            ->where('txn_at', '<=', $toDate.' 23:59:59')
            ->selectRaw('item_id, SUM(quantity * unit_cost) as total_cost, SUM(quantity) as total_qty')
            ->groupBy('item_id')
            ->get()
            ->mapWithKeys(function ($row) {
                $totalQty = (float) $row->total_qty;
                $rate = $totalQty > 0 ? ((float) $row->total_cost / $totalQty) : 0;

                return [(int) $row->item_id => round($rate, 2)];
            })
            ->all();
    }
}
