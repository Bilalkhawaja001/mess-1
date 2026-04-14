<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->string('return_number')->unique();
            $table->date('return_date');
            $table->decimal('qty_returned', 12, 3);
            $table->string('trans_unit_code', 20)->nullable();
            $table->decimal('trans_quantity', 12, 3)->nullable();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['goods_receipt_id', 'item_id']);
            $table->index(['item_id', 'return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_returns');
    }
};
