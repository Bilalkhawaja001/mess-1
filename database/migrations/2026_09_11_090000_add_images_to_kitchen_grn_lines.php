<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_grn_lines', function (Blueprint $t) {
            $t->string('image_path', 255)->nullable()->after('unit_cost');
            $t->string('image_disk', 20)->nullable()->after('image_path');
            $t->string('image_sha256', 64)->nullable()->after('image_disk');
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_grn_lines', function (Blueprint $t) {
            $t->dropColumn(['image_path', 'image_disk', 'image_sha256']);
        });
    }
};
