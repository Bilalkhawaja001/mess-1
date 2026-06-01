<?php

use App\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('app_settings')
            ->where('setting_key', AppSetting::MOBILE_CONTROL_KEY)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('app_settings')->insert([
            'setting_key' => AppSetting::MOBILE_CONTROL_KEY,
            'setting_value' => json_encode(AppSetting::mobileControlDefaults(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'value_type' => 'json',
            'is_active' => true,
            'updated_by_user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('app_settings')->where('setting_key', AppSetting::MOBILE_CONTROL_KEY)->delete();
    }
};
