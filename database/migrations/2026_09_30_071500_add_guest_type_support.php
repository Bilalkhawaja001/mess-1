<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('guests', 'guest_type')) {
            Schema::table('guests', function (Blueprint $table) {
                $table->string('guest_type', 20)
                    ->default('REGULAR')
                    ->after('guest_code');
            });
        }

        if (! Schema::hasColumn('guest_meals', 'guest_type_applied')) {
            Schema::table('guest_meals', function (Blueprint $table) {
                $table->string('guest_type_applied', 20)
                    ->default('REGULAR')
                    ->after('guest_id');
            });
        }

        DB::table('guests')
            ->whereNull('guest_type')
            ->orWhere('guest_type', '')
            ->update(['guest_type' => 'REGULAR']);

        DB::statement("
            UPDATE guest_meals gm
            INNER JOIN guests g ON g.id = gm.guest_id
            SET gm.guest_type_applied =
                CASE WHEN UPPER(g.guest_type) = 'VIP'
                     THEN 'VIP'
                     ELSE 'REGULAR'
                END
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('guest_meals', 'guest_type_applied')) {
            Schema::table('guest_meals', function (Blueprint $table) {
                $table->dropColumn('guest_type_applied');
            });
        }

        if (Schema::hasColumn('guests', 'guest_type')) {
            Schema::table('guests', function (Blueprint $table) {
                $table->dropColumn('guest_type');
            });
        }
    }
};
