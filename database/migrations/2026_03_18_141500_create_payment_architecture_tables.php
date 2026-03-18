<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->boolean('is_manual')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('bill_id')->nullable()->after('member_id')->constrained('billings')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->after('bill_id')->constrained('payment_methods')->nullOnDelete();
            $table->string('payment_ref', 80)->nullable()->after('payment_method_id');
            $table->string('currency', 10)->default('PKR')->after('amount');
            $table->decimal('refunded_amount', 14, 2)->default(0)->after('status');
            $table->decimal('reversed_amount', 14, 2)->default(0)->after('refunded_amount');
            $table->unsignedBigInteger('last_attempt_id')->nullable()->after('reversed_amount');
            $table->unsignedBigInteger('last_transaction_id')->nullable()->after('last_attempt_id');

            $table->unique(['payment_ref'], 'payments_payment_ref_unique');
            $table->index(['member_id', 'bill_id', 'status'], 'payments_member_bill_status_idx');
        });

        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('bill_id')->nullable()->constrained('billings')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('attempt_ref', 80)->unique();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('PKR');
            $table->string('status', 30)->default('PENDING');
            $table->json('audit_payload')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['member_id', 'bill_id', 'status'], 'payment_attempts_member_bill_status_idx');
            $table->index(['payment_id', 'created_at'], 'payment_attempts_payment_created_idx');
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('payment_attempt_id')->nullable()->constrained('payment_attempts')->nullOnDelete();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('bill_id')->nullable()->constrained('billings')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('internal_ref', 80)->unique();
            $table->string('external_ref', 120)->nullable();
            $table->string('merchant_ref', 120)->nullable();
            $table->string('idempotency_key', 120)->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('PKR');
            $table->string('status', 30)->default('INITIATED');
            $table->string('failure_reason', 255)->nullable();
            $table->json('raw_request_summary')->nullable();
            $table->json('raw_response_summary')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['idempotency_key'], 'payment_transactions_idempotency_key_unique');
            $table->index(['member_id', 'bill_id', 'status'], 'payment_transactions_member_bill_status_idx');
            $table->index(['external_ref', 'merchant_ref'], 'payment_transactions_ext_merchant_idx');
        });

        Schema::create('payment_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('bill_id')->nullable()->constrained('billings')->nullOnDelete();
            $table->string('status', 30)->default('RECONCILIATION_PENDING');
            $table->string('ledger_sync_status', 30)->default('PENDING');
            $table->string('accounting_sync_status', 30)->default('PENDING');
            $table->string('mismatch_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reconciled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'bill_id', 'status'], 'payment_reconciliations_member_bill_status_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('last_attempt_id')->references('id')->on('payment_attempts')->nullOnDelete();
            $table->foreign('last_transaction_id')->references('id')->on('payment_transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['last_attempt_id']);
            $table->dropForeign(['last_transaction_id']);
            $table->dropUnique('payments_payment_ref_unique');
            $table->dropIndex('payments_member_bill_status_idx');
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropConstrainedForeignId('bill_id');
            $table->dropColumn(['payment_ref', 'currency', 'refunded_amount', 'reversed_amount', 'last_attempt_id', 'last_transaction_id']);
        });

        Schema::dropIfExists('payment_reconciliations');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('payment_methods');
    }
};
