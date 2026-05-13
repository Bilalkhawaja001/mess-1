<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_returns', 'return_mode')) {
                $table->string('return_mode', 30)->default('STOCK')->after('unit_cost');
            }

            if (! Schema::hasColumn('vendor_returns', 'affects_stock')) {
                $table->boolean('affects_stock')->default(true)->after('return_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_returns', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_returns', 'affects_stock')) {
                $table->dropColumn('affects_stock');
            }

            if (Schema::hasColumn('vendor_returns', 'return_mode')) {
                $table->dropColumn('return_mode');
            }
        });
    }
};
