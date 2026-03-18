<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Hash;

class PasswordChangeService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function change(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, (string) $user->password)) {
            return false;
        }

        $user->password = $newPassword;
        $user->must_change_password = false;
        $user->save();

        $this->auditLogService->log('password.changed', User::class, (int) $user->id);

        return true;
    }
}
