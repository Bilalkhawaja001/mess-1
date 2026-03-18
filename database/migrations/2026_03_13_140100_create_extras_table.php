<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('extras', function (Blueprint $table) {
            $table->id();
            $table->date('extra_date');
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->string('description', 255);
            $table->decimal('amount', 14, 2);
            $table->foreignId('posted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['extra_date','member_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('extras'); }
};
