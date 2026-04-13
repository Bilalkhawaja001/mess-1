<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kitchen_issue_targets', function (Blueprint $table) {
            $table->id();
            $table->date('target_date');
            $table->foreignId('mess_id')->constrained('messes')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('required_qty', 12, 3);
            $table->decimal('issued_qty', 12, 3)->default(0);
            $table->string('status', 20)->default('open');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['target_date', 'mess_id', 'item_id'], 'kitchen_issue_targets_unique_context');
            $table->index(['target_date', 'mess_id', 'status'], 'kitchen_issue_targets_context_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_issue_targets');
    }
};
