<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $t) {
            $t->unsignedBigInteger('created_by_user_id')->nullable()->after('remarks');
            $t->unsignedBigInteger('created_by_staff_id')->nullable()->after('created_by_user_id');
            $t->unsignedBigInteger('approved_by_user_id')->nullable()->after('created_by_staff_id');
            $t->timestamp('approved_at')->nullable()->after('approved_by_user_id');
        });

        Schema::table('goods_receipts', function (Blueprint $t) {
            $t->unsignedBigInteger('created_by_user_id')->nullable()->after('remarks');
            $t->unsignedBigInteger('created_by_staff_id')->nullable()->after('created_by_user_id');
            $t->unsignedBigInteger('approved_by_user_id')->nullable()->after('created_by_staff_id');
            $t->timestamp('approved_at')->nullable()->after('approved_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $t) {
            $t->dropColumn(['created_by_user_id', 'created_by_staff_id', 'approved_by_user_id', 'approved_at']);
        });

        Schema::table('goods_receipts', function (Blueprint $t) {
            $t->dropColumn(['created_by_user_id', 'created_by_staff_id', 'approved_by_user_id', 'approved_at']);
        });
    }
};
