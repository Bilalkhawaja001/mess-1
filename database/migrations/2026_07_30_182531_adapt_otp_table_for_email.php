<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('member_registration_otps', 'email')) {
            DB::statement("ALTER TABLE `member_registration_otps` ADD `email` VARCHAR(120) NULL AFTER `member_id`");
        }
        DB::statement("ALTER TABLE `member_registration_otps` MODIFY `mobile_number` VARCHAR(20) NULL");
    }

    public function down(): void
    {
        if (Schema::hasColumn('member_registration_otps', 'email')) {
            DB::statement("ALTER TABLE `member_registration_otps` DROP COLUMN `email`");
        }
        DB::statement("ALTER TABLE `member_registration_otps` MODIFY `mobile_number` VARCHAR(20) NOT NULL");
    }
};
