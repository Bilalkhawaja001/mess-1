<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    public const MOBILE_CONTROL_KEY = 'mobile_app_control';

    protected $fillable = ['setting_key','setting_value','value_type','is_active','updated_by_user_id'];
    protected $casts = ['is_active'=>'boolean'];

    public static function mobileControlDefaults(): array
    {
        return [
            'mobile_app_enabled' => true,
            'features' => [
                'dashboard' => true,
                'bill' => true,
                'payments' => true,
                'statement' => true,
                'menu' => true,
                'complaint' => true,
                'profile' => true,
                'notification' => true,
            ],
            'support' => [
                'label' => 'Mess Office',
                'phone' => null,
                'message' => 'Please contact mess office for support.',
            ],
            'android' => [
                'min_version' => null,
                'latest_version' => null,
                'force_update' => false,
                'download_url' => null,
            ],
        ];
    }

    public static function mobileControl(): array
    {
        $defaults = self::mobileControlDefaults();
        $setting = self::query()
            ->where('setting_key', self::MOBILE_CONTROL_KEY)
            ->where('is_active', true)
            ->first();

        if (! $setting) {
            return $defaults;
        }

        $decoded = json_decode((string) $setting->setting_value, true);
        if (! is_array($decoded)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $decoded);
    }

    public static function publicPayload(): array
    {
        $control = self::mobileControl();

        return [
            'mobile_app_enabled' => (bool) data_get($control, 'mobile_app_enabled', true),
            'features' => [
                'dashboard' => (bool) data_get($control, 'features.dashboard', true),
                'bill' => (bool) data_get($control, 'features.bill', true),
                'payments' => (bool) data_get($control, 'features.payments', true),
                'statement' => (bool) data_get($control, 'features.statement', true),
                'menu' => (bool) data_get($control, 'features.menu', true),
                'complaint' => (bool) data_get($control, 'features.complaint', true),
                'profile' => (bool) data_get($control, 'features.profile', true),
                'notification' => (bool) data_get($control, 'features.notification', true),
            ],
            'support' => [
                'label' => (string) data_get($control, 'support.label', 'Mess Office'),
                'phone' => data_get($control, 'support.phone'),
                'message' => (string) data_get($control, 'support.message', 'Please contact mess office for support.'),
            ],
            'android' => [
                'min_version' => data_get($control, 'android.min_version'),
                'latest_version' => data_get($control, 'android.latest_version'),
                'force_update' => (bool) data_get($control, 'android.force_update', false),
                'download_url' => data_get($control, 'android.download_url'),
            ],
        ];
    }
}
