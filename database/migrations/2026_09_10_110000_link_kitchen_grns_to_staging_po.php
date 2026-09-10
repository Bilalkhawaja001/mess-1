<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_grns', function (Blueprint $t) {
            $t->unsignedBigInteger('kitchen_po_id')->nullable()->after('kitchen_staff_id')->index();
            $t->unsignedBigInteger('purchase_order_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_grns', function (Blueprint $t) {
            $t->dropIndex(['kitchen_po_id']);
            $t->dropColumn('kitchen_po_id');
        });
    }
};
