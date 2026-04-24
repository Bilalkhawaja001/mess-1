<?php

use App\Models\KitchenIssue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('stock_transactions')
            ->where('txn_type', 'KITCHEN_ISSUE')
            ->where(function ($query) {
                $query->whereNull('reference_type')
                    ->orWhereNull('reference_id');
            })
            ->delete();

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->unique(['txn_type', 'reference_type', 'reference_id'], 'stock_transactions_txn_ref_unique');
        });

        try {
            DB::statement("ALTER TABLE stock_transactions ADD CONSTRAINT chk_kitchen_issue_reference CHECK (txn_type <> 'KITCHEN_ISSUE' OR (reference_type = '".addslashes(KitchenIssue::class)."' AND reference_id IS NOT NULL))");
        } catch (\Throwable $e) {
            // ignore on engines that do not support named check constraints consistently
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE stock_transactions DROP CONSTRAINT chk_kitchen_issue_reference');
        } catch (\Throwable $e) {
            // ignore if engine does not expose named check constraints consistently
        }

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropUnique('stock_transactions_txn_ref_unique');
        });
    }
};
