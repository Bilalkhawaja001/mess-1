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
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $rows = AppSetting::query()->orderBy('setting_key')->get();

        $departmentStatus = strtolower((string) $request->query('department_status', 'all'));
        if (! in_array($departmentStatus, ['all', 'active', 'inactive'], true)) {
            $departmentStatus = 'all';
        }

        $departments = Department::query()
            ->when($departmentStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($departmentStatus === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->get();

        $messesStatus = strtolower((string) $request->query('messes_status', 'all'));
        if (! in_array($messesStatus, ['all', 'active', 'removed'], true)) {
            $messesStatus = 'all';
        }

        $messes = Mess::query()
            ->with('department')
            ->when($messesStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($messesStatus === 'removed', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->get();

        $departmentOptions = Department::query()->where('is_active', true)->orderBy('name')->get();
        $rates = RatePolicy::query()->orderBy('rate_type')->orderByDesc('effective_from')->get();

        $rateTypes = [
            'PER_DAY' => 'Per Day',
            'PER_MEAL' => 'Per Meal',
            'MONTHLY_FIXED' => 'Monthly Fixed',
            'GUEST' => 'Guest Meal Rate',
            'RATE_PER_DAY_EXECUTIVE' => 'Executive',
            'RATE_PER_DAY_CENTRALIZED' => 'Centralized',
            'RATE_PER_DAY_CONTRACTORS' => 'Contractors',
        ];

        $tab = (string) $request->query('tab', 'app');

        return view('admin.settings.index', compact(
            'rows',
            'departments',
            'messes',
            'departmentOptions',
            'rates',
            'rateTypes',
            'tab',
            'departmentStatus',
            'messesStatus',
        ));
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

    public function storeDepartment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:30|unique:departments,code',
        ]);

        Department::query()->create($data + ['is_active' => true]);

        return $this->redirectToTab('departments', 'Department created.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $department->name = $data['name'];
        $department->is_active = $request->boolean('is_active');
        $department->save();

        return $this->redirectToTab('departments', 'Department updated.');
    }

    public function removeDepartment(Department $department): RedirectResponse
    {
        $department->is_active = false;
        $department->save();

        return $this->redirectToTab('departments', 'Department removed.');
    }

    public function reactivateDepartment(Department $department): RedirectResponse
    {
        $department->is_active = true;
        $department->save();

        return $this->redirectToTab('departments', 'Department reactivated.');
    }

    public function storeMess(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:30|unique:messes,code',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        Mess::query()->create($data + ['is_active' => true]);

        return $this->redirectToTab('messes', 'Mess created.');
    }

    public function updateMess(Request $request, Mess $mess): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'is_active' => 'nullable|boolean',
        ]);

        $mess->name = $data['name'];
        $mess->department_id = $data['department_id'] ?? null;
        $mess->is_active = $request->boolean('is_active');
        $mess->save();

        return $this->redirectToTab('messes', 'Mess updated.');
    }

    public function removeMess(Mess $mess): RedirectResponse
    {
        $mess->is_active = false;
        $mess->save();

        return $this->redirectToTab('messes', 'Mess removed.');
    }

    public function reactivateMess(Mess $mess): RedirectResponse
    {
        $mess->is_active = true;
        $mess->save();

        return $this->redirectToTab('messes', 'Mess reactivated.');
    }

    private function redirectToTab(string $tab, string $message): RedirectResponse
    {
        return redirect()->route('admin.settings.index', [
            'tab' => $tab,
            'department_status' => request()->query('department_status', 'all'),
            'messes_status' => request()->query('messes_status', 'all'),
        ])->with('success', $message);
    }
}
