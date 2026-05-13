<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_menus', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_menus', 'mess_id')) {
                $table->unsignedBigInteger('mess_id')->nullable()->after('id');
                $table->index('mess_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_menus', function (Blueprint $table) {
            if (Schema::hasColumn('daily_menus', 'mess_id')) {
                $table->dropIndex(['mess_id']);
                $table->dropColumn('mess_id');
            }
        });
    }
};
