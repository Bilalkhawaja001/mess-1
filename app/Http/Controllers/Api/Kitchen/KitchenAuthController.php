<?php

namespace App\Http\Controllers\Api\Kitchen;

use App\Http\Controllers\Controller;
use App\Models\KitchenStaff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class KitchenAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string'],
        ]);

        $staff = KitchenStaff::where('username', $data['username'])->first();

        if (! $staff || ! $staff->is_active || ! Hash::check($data['password'], $staff->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        $token = Str::random(64);
        $staff->forceFill([
            'api_token_hash' => hash('sha256', $token),
            'last_login_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'token' => $token,
            'staff' => $this->payload($staff),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $staff = $this->staff($request);
        if (! $staff) {
            return $this->unauthenticated();
        }

        return response()->json(['success' => true, 'staff' => $this->payload($staff)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $staff = $this->staff($request);
        if (! $staff) {
            return $this->unauthenticated();
        }

        $staff->forceFill(['api_token_hash' => null])->save();

        return response()->json(['success' => true, 'message' => 'Logged out']);
    }

    protected function payload(KitchenStaff $staff): array
    {
        return [
            'id' => (int) $staff->id,
            'staff_code' => (string) $staff->staff_code,
            'name' => (string) $staff->name,
            'username' => (string) $staff->username,
            'can_view_outstanding' => (bool) $staff->can_view_outstanding,
        ];
    }

    protected function staff(Request $request): ?KitchenStaff
    {
        $token = $this->bearerToken($request);
        if ($token === '') {
            return null;
        }

        return KitchenStaff::where('api_token_hash', hash('sha256', $token))
            ->where('is_active', 1)
            ->first();
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

        $xToken = trim((string) $request->header('X-Staff-Token'));
        if ($xToken !== '') {
            return $xToken;
        }

        return trim((string) ($request->input('token') ?? $request->query('token') ?? ''));
    }

    protected function unauthenticated(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
    }
}
