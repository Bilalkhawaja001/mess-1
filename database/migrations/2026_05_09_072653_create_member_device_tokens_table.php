<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('device_token', 512);
            $table->string('platform', 30)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique('device_token', 'member_device_tokens_device_token_unique');
            $table->index(['user_id', 'member_id'], 'member_device_tokens_user_member_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_device_tokens');
    }
};
