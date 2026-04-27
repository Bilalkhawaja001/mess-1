<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_menus', function (Blueprint $table) {
            $table->id();
            $table->date('menu_date');
            $table->string('meal_type', 30);
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('items_text');
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['menu_date', 'meal_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_menus');
    }
};
