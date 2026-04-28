<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mess_costings', function (Blueprint $table) {
            $table->id();
            $table->string('month_cycle', 7);
            $table->foreignId('mess_id')->nullable()->constrained('messes')->nullOnDelete();
            $table->decimal('food_cost', 14, 2)->default(0);
            $table->decimal('gas_cost', 14, 2)->default(0);
            $table->boolean('include_gas_cost')->default(true);
            $table->decimal('salary_cost', 14, 2)->default(0);
            $table->boolean('include_salary_cost')->default(true);
            $table->decimal('other_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->unsignedInteger('member_count')->default(0);
            $table->decimal('active_days_total', 14, 3)->default(0);
            $table->decimal('cost_per_member', 14, 2)->default(0);
            $table->decimal('cost_per_day', 14, 4)->default(0);
            $table->json('comparison_json')->nullable();
            $table->json('snapshot_json')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['month_cycle', 'mess_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mess_costings');
    }
};
