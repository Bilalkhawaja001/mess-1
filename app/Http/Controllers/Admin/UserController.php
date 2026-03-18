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
        $roles = Role::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.users.index', compact('rows', 'roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::query()->create([
            'username' => $request->string('username')->toString(),
            'name' => $request->string('name')->toString(),
            'email' => $request->input('email') ?: null,
            'password' => Hash::make((string) $request->input('password')),
            'role_id' => (int) $request->input('role_id'),
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
            $user->password = Hash::make((string) $request->input('password'));
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

        $user->is_active = ! $user->is_active;
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User status updated.');
    }
}
