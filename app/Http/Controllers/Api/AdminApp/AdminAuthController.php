<?php

namespace App\Http\Controllers\Api\AdminApp;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    protected const APP_ROLE = 'SUPER_ADMIN';

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->whereNull('member_id')
            ->where(function ($w) use ($data) {
                $w->where('username', $data['username'])
                    ->orWhere('email', $data['username']);
            })
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['success' => false, 'message' => 'Account inactive'], 403);
        }

        if (optional($user->role)->code !== self::APP_ROLE) {
            return response()->json(['success' => false, 'message' => 'App access not allowed'], 403);
        }

        $token = Str::random(64);
        $user->forceFill(['app_token_hash' => hash('sha256', $token)])->save();

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $this->payload($user),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $this->user($request);
        if (! $user) {
            return $this->unauthenticated();
        }

        return response()->json(['success' => true, 'user' => $this->payload($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $this->user($request);
        if (! $user) {
            return $this->unauthenticated();
        }

        $user->forceFill(['app_token_hash' => null])->save();

        return response()->json(['success' => true, 'message' => 'Logged out']);
    }

    protected function payload(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'role' => optional($user->role)->code,
            'can' => [
                'approve_procurement' => $user->hasPermission('procurement.manage'),
                'approve_payment' => $user->hasPermission('payment.approve'),
                'record_payment' => $user->hasPermission('payments.manual_record_admin'),
                'view_outstanding' => $user->hasPermission('payments.view_admin'),
            ],
        ];
    }

    protected function user(Request $request): ?User
    {
        $token = $this->bearerToken($request);
        if ($token === '') {
            return null;
        }

        $user = User::where('app_token_hash', hash('sha256', $token))
            ->where('is_active', 1)
            ->first();

        if (! $user || optional($user->role)->code !== self::APP_ROLE) {
            return null;
        }

        return $user;
    }

    protected function requirePermission(Request $request, string $code): ?User
    {
        $user = $this->user($request);
        if (! $user || ! $user->hasPermission($code)) {
            return null;
        }

        return $user;
    }

    protected function bearerToken(Request $request): string
    {
        $headers = [
            (string) $request->header('Authorization'),
            (string) $request->server('HTTP_AUTHORIZATION'),
            (string) $request->server('REDIRECT_HTTP_AUTHORIZATION'),
            (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''),
            (string) ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''),
        ];

        foreach ($headers as $header) {
            $header = trim($header);
            if (stripos($header, 'Bearer ') === 0) {
                return trim(substr($header, 7));
            }
        }

        $xToken = trim((string) $request->header('X-Admin-Token'));
        if ($xToken !== '') {
            return $xToken;
        }

        return trim((string) ($request->input('token') ?? $request->query('token') ?? ''));
    }

    protected function unauthenticated(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
    }

    protected function forbidden(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not allowed'], 403);
    }
}
