<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 120);
            $table->string('entity_type', 120)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('route', 255)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['action', 'created_at']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_hash', 255)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });

        Schema::create('month_closures', function (Blueprint $table) {
            $table->id();
            $table->string('month_cycle', 7)->unique();
            $table->string('status', 20)->default('OPEN');
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('reopened_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->foreignId('hard_reset_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hard_reset_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'month_cycle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('month_closures');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
