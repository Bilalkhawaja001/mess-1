<?php

namespace App\Http\Controllers\Admin\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\FleetSettingRequest;
use App\Models\Fleet\FleetSetting;
use Illuminate\Support\Facades\DB;

class FleetSettingController extends Controller
{
    /**
     * Key/value settings only. No status column, no fake CRUD.
     */
    public function index()
    {
        $settings = FleetSetting::query()
            ->orderBy('key')
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        $defaults = [
            'alert_days' => 30,
            'fuel_average_min_km_per_liter' => 4,
            'fuel_average_max_km_per_liter' => 18,
            'maintenance_due_km_buffer' => 500,
            'maintenance_due_days_buffer' => 7,
        ];

        $settings = array_merge($defaults, $settings);

        return view('admin.fleet.settings.index', compact('settings'));
    }

    public function update(FleetSettingRequest $request)
    {
        $settings = $request->validated('settings');

        DB::transaction(function () use ($settings) {
            foreach ($settings as $key => $value) {
                FleetSetting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        return redirect()->route('admin.fleet.settings.index')->with('success', 'Fleet settings updated.');
    }
}
