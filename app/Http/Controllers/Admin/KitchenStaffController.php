<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KitchenStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class KitchenStaffController extends Controller
{
    public function index(): View
    {
        $staff = KitchenStaff::query()
            ->withCount(['purchaseOrders', 'goodsReceipts'])
            ->orderByDesc('id')
            ->get();

        return view('admin.kitchen-staff.index', compact('staff'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'staff_code' => ['required', 'string', 'max:50', 'unique:kitchen_staff,staff_code'],
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'unique:kitchen_staff,username'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
        ]);

        KitchenStaff::create([
            'staff_code' => $data['staff_code'],
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'mobile_number' => $data['mobile_number'] ?? null,
            'is_active' => (bool) $request->boolean('is_active'),
            'can_view_outstanding' => (bool) $request->boolean('can_view_outstanding'),
            'created_by_user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Kitchen staff created.');
    }

    public function update(Request $request, KitchenStaff $kitchenStaff): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
        ]);

        $kitchenStaff->update([
            'name' => $data['name'],
            'mobile_number' => $data['mobile_number'] ?? null,
            'is_active' => (bool) $request->boolean('is_active'),
            'can_view_outstanding' => (bool) $request->boolean('can_view_outstanding'),
        ]);

        if (! $kitchenStaff->is_active) {
            $kitchenStaff->forceFill(['api_token_hash' => null])->save();
        }

        return back()->with('success', 'Kitchen staff updated.');
    }

    public function resetPassword(Request $request, KitchenStaff $kitchenStaff): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ]);

        $kitchenStaff->forceFill([
            'password' => Hash::make($data['password']),
            'api_token_hash' => null,
        ])->save();

        return back()->with('success', 'Password reset. Staff must log in again.');
    }
}
