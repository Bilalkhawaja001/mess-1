<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('member_registration_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('mobile_number', 20);
            $table->string('otp_hash', 255);
            $table->timestamp('expires_at');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('resend_count')->default(0);
            $table->timestamp('last_sent_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('status', 20)->default('SENT');
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_registration_otps');
    }
};
