<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            if (! Schema::hasColumn('billings', 'due_date')) {
                $table->date('due_date')->nullable()->after('net_payable');
            }
        });
    }

    public function down(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            if (Schema::hasColumn('billings', 'due_date')) {
                $table->dropColumn('due_date');
            }
        });
    }
};
