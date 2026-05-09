<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'max:30'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();

        $memberId = $user->member_id;

        MemberDeviceToken::query()->updateOrCreate(
            ['device_token' => $data['device_token']],
            [
                'user_id' => $user->id,
                'member_id' => $memberId,
                'platform' => $data['platform'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'message' => 'Device token saved.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_token' => ['required', 'string', 'max:512'],
        ]);

        MemberDeviceToken::query()
            ->where('device_token', $data['device_token'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Device token removed.',
        ]);
    }
}
