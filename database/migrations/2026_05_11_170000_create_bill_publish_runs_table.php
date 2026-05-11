<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_publish_runs', function (Blueprint $table) {
            $table->id();
            $table->string('month_cycle', 7);
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('bill_count')->default(0);
            $table->decimal('total_bill_amount', 14, 2)->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();

            $table->index(['month_cycle', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_publish_runs');
    }
};
