<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.app-settings.edit', [
            'settings' => AppSetting::mobileControl(),
            'publicPayload' => AppSetting::publicPayload(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'mobile_app_enabled' => ['nullable', 'boolean'],
            'features' => ['nullable', 'array'],
            'features.dashboard' => ['nullable', 'boolean'],
            'features.bill' => ['nullable', 'boolean'],
            'features.payments' => ['nullable', 'boolean'],
            'features.statement' => ['nullable', 'boolean'],
            'features.menu' => ['nullable', 'boolean'],
            'features.complaint' => ['nullable', 'boolean'],
            'features.profile' => ['nullable', 'boolean'],
            'features.notification' => ['nullable', 'boolean'],
            'support.label' => ['nullable', 'string', 'max:120'],
            'support.phone' => ['nullable', 'string', 'max:60'],
            'support.message' => ['nullable', 'string', 'max:500'],
            'android.min_version' => ['nullable', 'string', 'max:40'],
            'android.latest_version' => ['nullable', 'string', 'max:40'],
            'android.force_update' => ['nullable', 'boolean'],
            'android.download_url' => ['nullable', 'url', 'max:500'],
        ]);

        $defaults = AppSetting::mobileControlDefaults();
        $settings = [
            'mobile_app_enabled' => (bool) ($payload['mobile_app_enabled'] ?? false),
            'features' => [],
            'support' => [
                'label' => $payload['support']['label'] ?? $defaults['support']['label'],
                'phone' => $payload['support']['phone'] ?? null,
                'message' => $payload['support']['message'] ?? $defaults['support']['message'],
            ],
            'android' => [
                'min_version' => $payload['android']['min_version'] ?? null,
                'latest_version' => $payload['android']['latest_version'] ?? null,
                'force_update' => (bool) data_get($payload, 'android.force_update', false),
                'download_url' => $payload['android']['download_url'] ?? null,
            ],
        ];

        foreach (array_keys($defaults['features']) as $feature) {
            $settings['features'][$feature] = (bool) data_get($payload, 'features.'.$feature, false);
        }

        AppSetting::query()->updateOrCreate(
            ['setting_key' => AppSetting::MOBILE_CONTROL_KEY],
            [
                'setting_value' => json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'value_type' => 'json',
                'is_active' => true,
                'updated_by_user_id' => $request->user()?->id,
            ]
        );

        return redirect()->route('admin.app-settings.edit')->with('success', 'Mobile app settings updated.');
    }
}
