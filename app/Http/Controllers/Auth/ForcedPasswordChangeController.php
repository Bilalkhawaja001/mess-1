<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ForcedPasswordChangeController extends Controller
{
    public function edit()
    {
        return view('auth.force-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        try {
            $this->changePassword($request);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $user = $request->user();

        if ($user->isMemberRole()) {
            return redirect()->route('member.dashboard')->with('success', 'Password changed successfully.');
        }

        if (optional($user->role)->code === 'DATA_ENTRY') {
            return redirect()->route('admin.attendance.index')->with('success', 'Password changed successfully.');
        }

        return redirect()->route('admin.dashboard')->with('success', 'Password changed successfully.');
    }

    public function apiUpdate(Request $request): JsonResponse
    {
        $this->changePassword($request);

        $user = $request->user();

        return response()->json([
            'message' => 'Password changed successfully.',
            'redirect' => $user->isMemberRole()
                ? route('member.dashboard')
                : (optional($user->role)->code === 'DATA_ENTRY'
                    ? route('admin.attendance.index')
                    : route('admin.dashboard')),
        ]);
    }

    private function changePassword(Request $request): void
    {
        $payload = $request->only(['new_password', 'confirm_password']);

        if (! array_key_exists('new_password', $payload) && $request->has('password')) {
            $payload['new_password'] = $request->input('password');
        }

        if (! array_key_exists('confirm_password', $payload) && $request->has('password_confirmation')) {
            $payload['confirm_password'] = $request->input('password_confirmation');
        }

        $validator = Validator::make($payload, [
            'new_password' => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'same:new_password'],
        ], [
            'new_password.min' => 'Password must be at least 6 characters.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = $request->user();

        if (! $user->must_change_password && Hash::check((string) $payload['new_password'], $user->password)) {
            return;
        }

        if (Hash::check((string) $payload['new_password'], $user->password)) {
            throw ValidationException::withMessages([
                'new_password' => 'New password cannot be same as old password.',
            ]);
        }

        $user->forceFill([
            'password' => (string) $payload['new_password'],
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();
    }
}
