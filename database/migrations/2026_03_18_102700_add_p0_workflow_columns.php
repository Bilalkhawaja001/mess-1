<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            $table->unsignedBigInteger('reversed_from_billing_id')->nullable()->after('generated_by_user_id');
            $table->string('billing_status', 20)->default('POSTED')->after('reversed_from_billing_id');
            $table->text('correction_reason')->nullable()->after('billing_status');
            $table->foreign('reversed_from_billing_id')->references('id')->on('billings')->nullOnDelete();
            $table->index(['month_cycle', 'billing_status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('edited_by_user_id')->nullable()->after('approved_by_user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('edited_at')->nullable()->after('edited_by_user_id');
            $table->text('edit_reason')->nullable()->after('edited_at');
        });

        Schema::table('member_ledgers', function (Blueprint $table) {
            $table->boolean('is_opening_balance')->default(false)->after('reason_code');
            $table->index(['member_id', 'is_opening_balance']);
        });
    }

    public function down(): void
    {
        Schema::table('member_ledgers', function (Blueprint $table) {
            $table->dropIndex(['member_id', 'is_opening_balance']);
            $table->dropColumn('is_opening_balance');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('edited_by_user_id');
            $table->dropColumn(['edited_at', 'edit_reason']);
        });

        Schema::table('billings', function (Blueprint $table) {
            $table->dropForeign(['reversed_from_billing_id']);
            $table->dropIndex(['month_cycle', 'billing_status']);
            $table->dropColumn(['reversed_from_billing_id', 'billing_status', 'correction_reason']);
        });
    }
};
