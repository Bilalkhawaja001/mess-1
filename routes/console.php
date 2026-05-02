<?php

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\StockTransaction;
use App\Models\VendorReturn;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

Artisan::command('audit:legacy-inventory-lineage', function () {
    $report = [
        'goods_receipt_lines_missing_purchase_order_line_id' => [],
        'grn_stock_transactions_still_pointing_to_goods_receipts' => [],
        'vendor_returns_missing_goods_receipt_line_id' => [],
        'ambiguous_grn_stock_transaction_matches' => [],
        'ambiguous_vendor_return_matches' => [],
    ];

    if (Schema::hasColumn('goods_receipt_lines', 'purchase_order_line_id')) {
        $report['goods_receipt_lines_missing_purchase_order_line_id'] = GoodsReceiptLine::query()
            ->with('goodsReceipt.purchaseOrder')
            ->whereNull('purchase_order_line_id')
            ->orderBy('id')
            ->get()
            ->map(function (GoodsReceiptLine $line): array {
                return [
                    'goods_receipt_line_id' => $line->id,
                    'goods_receipt_id' => $line->goods_receipt_id,
                    'purchase_order_id' => $line->goodsReceipt?->purchase_order_id,
                    'item_id' => $line->item_id,
                    'qty_received' => (float) $line->qty_received,
                ];
            })
            ->all();
    }

    $legacyGrnTransactions = StockTransaction::query()
        ->where('txn_type', 'GRN')
        ->where('reference_type', GoodsReceipt::class)
        ->orderBy('id')
        ->get();

    foreach ($legacyGrnTransactions as $txn) {
        $matches = GoodsReceiptLine::query()
            ->where('goods_receipt_id', $txn->reference_id)
            ->where('item_id', $txn->item_id)
            ->when($txn->trans_quantity !== null, fn ($query) => $query->where('qty_received', $txn->trans_quantity))
            ->orderBy('id')
            ->get(['id', 'goods_receipt_id', 'item_id', 'qty_received', 'purchase_order_line_id']);

        $row = [
            'stock_transaction_id' => $txn->id,
            'goods_receipt_id' => $txn->reference_id,
            'item_id' => $txn->item_id,
            'trans_quantity' => $txn->trans_quantity !== null ? (float) $txn->trans_quantity : null,
            'candidate_goods_receipt_line_ids' => $matches->pluck('id')->all(),
        ];

        $report['grn_stock_transactions_still_pointing_to_goods_receipts'][] = $row;

        if ($matches->count() > 1) {
            $report['ambiguous_grn_stock_transaction_matches'][] = $row;
        }
    }

    if (Schema::hasColumn('vendor_returns', 'goods_receipt_line_id')) {
        $legacyVendorReturns = VendorReturn::query()
            ->whereNull('goods_receipt_line_id')
            ->orderBy('id')
            ->get();

        foreach ($legacyVendorReturns as $return) {
            $matches = GoodsReceiptLine::query()
                ->where('goods_receipt_id', $return->goods_receipt_id)
                ->where('item_id', $return->item_id)
                ->when($return->qty_returned !== null, fn ($query) => $query->where('qty_received', '>=', $return->qty_returned))
                ->orderBy('id')
                ->get(['id', 'goods_receipt_id', 'item_id', 'qty_received', 'purchase_order_line_id']);

            $row = [
                'vendor_return_id' => $return->id,
                'goods_receipt_id' => $return->goods_receipt_id,
                'item_id' => $return->item_id,
                'qty_returned' => (float) $return->qty_returned,
                'candidate_goods_receipt_line_ids' => $matches->pluck('id')->all(),
            ];

            $report['vendor_returns_missing_goods_receipt_line_id'][] = $row;

            if ($matches->count() > 1) {
                $report['ambiguous_vendor_return_matches'][] = $row;
            }
        }
    }

    $this->line(json_encode([
        'summary' => [
            'goods_receipt_lines_missing_purchase_order_line_id' => count($report['goods_receipt_lines_missing_purchase_order_line_id']),
            'grn_stock_transactions_still_pointing_to_goods_receipts' => count($report['grn_stock_transactions_still_pointing_to_goods_receipts']),
            'vendor_returns_missing_goods_receipt_line_id' => count($report['vendor_returns_missing_goods_receipt_line_id']),
            'ambiguous_grn_stock_transaction_matches' => count($report['ambiguous_grn_stock_transaction_matches']),
            'ambiguous_vendor_return_matches' => count($report['ambiguous_vendor_return_matches']),
        ],
        'details' => $report,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
})->purpose('Read-only audit for legacy GRN and vendor return lineage/backfill risks');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
