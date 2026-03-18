<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->string('month_cycle', 7);
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->unsignedInteger('active_days')->default(0);
            $table->decimal('rate_per_day', 12, 2)->default(0);
            $table->decimal('base_amount', 14, 2)->default(0);
            $table->decimal('extras_amount', 14, 2)->default(0);
            $table->decimal('net_payable', 14, 2)->default(0);
            $table->boolean('is_locked')->default(true);
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['month_cycle', 'member_id']);
            $table->index(['month_cycle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};
