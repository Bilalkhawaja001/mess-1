<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSettingRequest;
use App\Models\AppSetting;
use App\Models\Department;
use App\Models\Mess;
use App\Models\RatePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $rows = AppSetting::query()->orderBy('setting_key')->get();
        $departments = Department::query()->orderBy('name')->get();
        $messes = Mess::query()->with('department')->orderBy('name')->get();
        $rates = RatePolicy::query()->orderBy('rate_type')->orderByDesc('effective_from')->get();

        $rateTypes = [
            'PER_DAY' => 'Per Day',
            'PER_MEAL' => 'Per Meal',
            'MONTHLY_FIXED' => 'Monthly Fixed',
            'GUEST' => 'Guest Meal Rate',
            'RATE_PER_DAY_EXECUTIVE' => 'Executive Mess Rate',
            'RATE_PER_DAY_CENTRALIZED' => 'Centralized Mess Rate',
            'RATE_PER_DAY_CONTRACTORS' => 'Contractors Mess Rate',
        ];

        $tab = (string) $request->query('tab', 'app');

        return view('admin.settings.index', compact('rows', 'departments', 'messes', 'rates', 'rateTypes', 'tab'));
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

        return redirect()->route('admin.settings.index', ['tab' => 'app'])->with('success', 'Setting saved.');
    }

    public function toggle(AppSetting $setting): RedirectResponse
    {
        $setting->is_active = ! $setting->is_active;
        $setting->updated_by_user_id = Auth::id();
        $setting->save();

        return back()->with('success', 'Setting status updated.');
    }
}
