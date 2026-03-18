<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->date('attendance_date');
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->boolean('present')->default(false);
            $table->boolean('breakfast')->default(false);
            $table->boolean('lunch')->default(false);
            $table->boolean('dinner')->default(false);
            $table->foreignId('marked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['attendance_date', 'member_id']);
            $table->index(['attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
