<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcedPasswordChangeController extends Controller
{
    public function edit()
    {
        return view('auth.force-password-change');
    }

    public function update(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (Hash::check($request->password, $user->password)) {
            return back()
                ->withErrors(['password' => 'New password cannot be same as old password.'])
                ->withInput();
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        return redirect()->intended('/')->with('success', 'Password changed successfully.');
    }
}
