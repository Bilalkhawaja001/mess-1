<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rate_policies', function (Blueprint $table) {
            $table->id();
            $table->string('rate_type', 50);
            $table->decimal('value', 14, 4);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['rate_type','effective_from']);
        });
    }
    public function down(): void { Schema::dropIfExists('rate_policies'); }
};
