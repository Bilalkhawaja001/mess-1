<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('monthly_attendances', function (Blueprint $table) {
            $table->id();
            $table->string('month_cycle', 7);
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->unsignedInteger('present_days')->default(0);
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->unique(['month_cycle','member_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('monthly_attendances'); }
};
