<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('member_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->date('entry_date');
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('ref_type', 30);
            $table->unsignedBigInteger('ref_id')->default(0);
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->string('reason_code', 80)->nullable();
            $table->foreignId('posted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['member_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_ledgers');
    }
};
