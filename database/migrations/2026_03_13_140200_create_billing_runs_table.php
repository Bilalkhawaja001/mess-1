<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('billing_runs', function (Blueprint $table) {
            $table->id();
            $table->string('month_cycle', 7);
            $table->string('scope_hash', 64);
            $table->string('config_hash', 64);
            $table->string('status', 20)->default('DONE');
            $table->unsignedInteger('inserted_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['month_cycle','scope_hash']);
        });
    }
    public function down(): void { Schema::dropIfExists('billing_runs'); }
};
