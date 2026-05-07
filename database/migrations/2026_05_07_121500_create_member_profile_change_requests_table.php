<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('member_profile_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('field_name', 20);
            $table->string('old_value')->nullable();
            $table->string('new_value');
            $table->string('status', 20)->default('PENDING');
            $table->text('admin_remarks')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index(['field_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_profile_change_requests');
    }
};
