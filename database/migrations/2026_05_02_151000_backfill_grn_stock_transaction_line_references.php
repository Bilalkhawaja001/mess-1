<?php

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\StockTransaction;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        StockTransaction::query()
            ->where('txn_type', 'GRN')
            ->where('reference_type', GoodsReceipt::class)
            ->orderBy('id')
            ->get()
            ->each(function (StockTransaction $txn): void {
                $line = GoodsReceiptLine::query()
                    ->where('goods_receipt_id', $txn->reference_id)
                    ->where('item_id', $txn->item_id)
                    ->when($txn->trans_quantity !== null, fn ($query) => $query->where('qty_received', $txn->trans_quantity))
                    ->orderBy('id')
                    ->first();

                if (! $line) {
                    return;
                }

                $txn->forceFill([
                    'reference_type' => GoodsReceiptLine::class,
                    'reference_id' => $line->id,
                ])->save();
            });
    }

    public function down(): void
    {
        StockTransaction::query()
            ->where('txn_type', 'GRN')
            ->where('reference_type', GoodsReceiptLine::class)
            ->orderBy('id')
            ->get()
            ->each(function (StockTransaction $txn): void {
                $line = GoodsReceiptLine::query()->find($txn->reference_id);
                if (! $line) {
                    return;
                }

                $txn->forceFill([
                    'reference_type' => GoodsReceipt::class,
                    'reference_id' => $line->goods_receipt_id,
                ])->save();
            });
    }
};
