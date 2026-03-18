<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_code', 50)->unique();
            $table->string('name', 120);
            $table->string('department_name', 120)->nullable();
            $table->date('join_date');
            $table->date('leave_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['member_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
