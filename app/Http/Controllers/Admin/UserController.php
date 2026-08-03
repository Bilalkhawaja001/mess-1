<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $rows = User::query()->with('role')->orderBy('id')->get();

        $actor = Auth::user();
        $roles = Role::query()
            ->where('is_active', true)
            ->when(
                optional($actor->role)->code === 'DATA_ENTRY',
                fn ($query) => $query->whereNotIn('code', ['SUPER_ADMIN', 'ADMIN'])
            )
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('rows', 'roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $actor = Auth::user();
        $roleId = (int) $request->input('role_id');

        if (optional($actor->role)->code === 'DATA_ENTRY') {
            $targetRole = Role::query()->findOrFail($roleId);

            if (in_array($targetRole->code, ['SUPER_ADMIN', 'ADMIN'], true)) {
                abort(403, 'DATA_ENTRY cannot create privileged accounts.');
            }
        }

        User::query()->create([
            'username' => $request->string('username')->toString(),
            'name' => $request->string('name')->toString(),
            'email' => $request->input('email') ?: null,
            'password' => (string) $request->input('password'),
            'role_id' => $roleId,
            'is_active' => (bool) $request->boolean('is_active', true),
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->username = $request->string('username')->toString();
        $user->name = $request->string('name')->toString();
        $user->email = $request->input('email') ?: null;
        $user->role_id = (int) $request->input('role_id');
        $user->is_active = (bool) $request->boolean('is_active');

        if ($request->filled('password')) {
            $user->password = (string) $request->input('password');
            $user->must_change_password = true;
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $actor = Auth::user();

        if ($actor && $actor->id === $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot deactivate your own account.');
        }

        if (
            $actor
            && optional($actor->role)->code === 'DATA_ENTRY'
            && in_array(optional($user->role)->code, ['SUPER_ADMIN', 'ADMIN'], true)
        ) {
            abort(403, 'DATA_ENTRY cannot change privileged account status.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User status updated.');
    }
}
