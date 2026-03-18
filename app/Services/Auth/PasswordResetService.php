<?php

namespace App\Services\Auth;

use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function issueToken(string $username): ?string
    {
        $user = User::query()->where('username', $username)->first();
        if (! $user) {
            return null;
        }

        $token = Str::random(64);
        PasswordResetToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => Hash::make($token),
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->auditLogService->log('password.reset.requested', User::class, (int) $user->id);

        return $token;
    }

    public function consumeToken(string $token, string $password): bool
    {
        $row = PasswordResetToken::query()->whereNull('used_at')->orderByDesc('id')->get()->first(
            fn ($candidate) => Hash::check($token, (string) $candidate->token_hash)
        );

        if (! $row || $row->expires_at->isPast()) {
            return false;
        }

        $user = User::query()->find($row->user_id);
        if (! $user) {
            return false;
        }

        $user->password = $password;
        $user->must_change_password = false;
        $user->save();

        $row->used_at = now();
        $row->save();

        $this->auditLogService->log('password.reset.completed', User::class, (int) $user->id);

        return true;
    }
}
