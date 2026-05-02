<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('goods_receipt_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('goods_receipt_lines', 'purchase_order_line_id')) {
                $table->foreignId('purchase_order_line_id')->nullable()->after('goods_receipt_id')->constrained('purchase_order_lines')->nullOnDelete();
            }
        });

        Schema::table('vendor_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_returns', 'goods_receipt_line_id')) {
                $table->foreignId('goods_receipt_line_id')->nullable()->after('goods_receipt_id')->constrained('goods_receipt_lines')->nullOnDelete();
                $table->index(['goods_receipt_line_id', 'return_date'], 'vendor_returns_grn_line_return_date_idx');
            }
        });

    }

    public function down(): void
    {
        Schema::table('vendor_returns', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_returns', 'goods_receipt_line_id')) {
                $table->dropIndex('vendor_returns_grn_line_return_date_idx');
                $table->dropConstrainedForeignId('goods_receipt_line_id');
            }
        });

        Schema::table('goods_receipt_lines', function (Blueprint $table) {
            if (Schema::hasColumn('goods_receipt_lines', 'purchase_order_line_id')) {
                $table->dropConstrainedForeignId('purchase_order_line_id');
            }
        });
    }
};
