<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained('stock_counts')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('system_qty', 12, 3)->default(0);
            $table->decimal('counted_qty', 12, 3)->default(0);
            $table->decimal('variance_qty', 12, 3)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['stock_count_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_lines');
    }
};
