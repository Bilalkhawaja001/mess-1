<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSettingRequest;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $rows = AppSetting::query()->orderBy('setting_key')->get();
        return view('admin.settings.index', compact('rows'));
    }

    public function store(StoreSettingRequest $request): RedirectResponse
    {
        AppSetting::query()->updateOrCreate(
            ['setting_key' => $request->input('setting_key')],
            [
                'setting_value' => $request->input('setting_value'),
                'value_type' => $request->input('value_type'),
                'is_active' => $request->boolean('is_active', true),
                'updated_by_user_id' => Auth::id(),
            ]
        );

        return redirect()->route('admin.settings.index')->with('success', 'Setting saved.');
    }

    public function toggle(AppSetting $setting): RedirectResponse
    {
        $setting->is_active = ! $setting->is_active;
        $setting->updated_by_user_id = Auth::id();
        $setting->save();

        return back()->with('success', 'Setting status updated.');
    }
}
