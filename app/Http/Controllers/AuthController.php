<?php

namespace App\Http\Controllers;

use App\Services\Auth\PasswordChangeService;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function showChangePasswordForm(): View
    {
        return view('auth.change_password');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (config('turnstile.enabled') && ! $this->verifyTurnstile($request)) {
            return back()->withInput()->withErrors(['turnstile' => 'Verification failed. Please try again.']);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput()->with('error', 'Invalid credentials.');
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if ($user->isMemberRole()) {
            return redirect()->route('member.dashboard');
        }

        if (optional($user->role)->code === 'DATA_ENTRY') {
            return redirect()->route('admin.attendance.index');
        }

        return redirect()->route('admin.dashboard');
    }

    public function requestPasswordReset(Request $request, PasswordResetService $service): RedirectResponse
    {
        $payload = $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $service->issueToken((string) $payload['identifier']);

        return back()->with('success', 'If this account exists and has a valid email, reset instructions have been sent.');
    }

    public function consumePasswordReset(Request $request, PasswordResetService $service): RedirectResponse
    {
        $payload = $request->validate([
            'token' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $ok = $service->consumeToken((string) $payload['token'], (string) $payload['new_password']);
        if (! $ok) {
            return back()->with('error', 'Invalid or expired reset link.');
        }

        return redirect()->route('login')->with('success', 'Password reset completed. You can now login with your new password.');
    }

    public function changePassword(Request $request, PasswordChangeService $service): RedirectResponse
    {
        $payload = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        $ok = $service->change(Auth::user(), (string) $payload['current_password'], (string) $payload['new_password']);
        if (! $ok) {
            return back()->with('error', 'Current password mismatch.');
        }

        return back()->with('success', 'Password changed.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out.');
    }

    private function verifyTurnstile(Request $request): bool
    {
        $token = (string) $request->input('cf-turnstile-response', '');
        if ($token === '') {
            return false;
        }
        try {
            $resp = \Illuminate\Support\Facades\Http::asForm()->timeout(10)->post(
                config('turnstile.verify_url'),
                [
                    'secret'   => config('turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]
            );
            return (bool) ($resp->json('success') ?? false);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Turnstile verify error: '.$e->getMessage());
            return false;
        }
    }
}
