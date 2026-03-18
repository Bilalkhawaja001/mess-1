<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::query()->where('code', 'SUPER_ADMIN')->firstOrFail();

        $email = (string) env('SUPERADMIN_EMAIL', 'superadmin@bootstrap.invalid');
        $username = (string) env('SUPERADMIN_USERNAME', 'superadmin_bootstrap');
        $name = (string) env('SUPERADMIN_NAME', 'Default Super Admin');

        $existingSuperAdmins = User::query()
            ->where('role_id', $role->id)
            ->orderBy('id')
            ->get();

        $existingByRole = $existingSuperAdmins->first();

        if ($existingByRole) {
            $tempPassword = Str::password(24, true, true, true, false);

            $existingByRole->forceFill([
                'password' => $tempPassword,
                'must_change_password' => true,
                'is_active' => true,
            ])->save();

            $demotedUserIds = [];
            if ($existingSuperAdmins->count() > 1) {
                $adminRole = Role::query()->where('code', 'ADMIN')->firstOrFail();
                $extraIds = $existingSuperAdmins->slice(1)->pluck('id')->all();
                User::query()
                    ->whereIn('id', $extraIds)
                    ->update([
                        'role_id' => $adminRole->id,
                        'must_change_password' => true,
                    ]);
                $demotedUserIds = $extraIds;
            }

            File::put(
                storage_path('app/superadmin_provisioning_runtime.json'),
                json_encode([
                    'status' => 'already_exists_password_rotated',
                    'user_id' => $existingByRole->id,
                    'email' => $existingByRole->email,
                    'username' => $existingByRole->username,
                    'name' => $existingByRole->name,
                    'must_change_password' => (bool) $existingByRole->must_change_password,
                    'temp_password' => $tempPassword,
                    'demoted_extra_super_admin_user_ids' => $demotedUserIds,
                    'generated_at' => now()->toIso8601String(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            return;
        }

        $conflict = User::query()
            ->where('email', $email)
            ->orWhere('username', $username)
            ->first();

        if ($conflict) {
            throw new \RuntimeException(sprintf(
                'Cannot provision SUPER_ADMIN: email/username conflict with existing user id=%d. Set SUPERADMIN_EMAIL/SUPERADMIN_USERNAME overrides.',
                $conflict->id
            ));
        }

        $tempPassword = Str::password(24, true, true, true, false);

        $user = User::query()->create([
            'role_id' => $role->id,
            'username' => $username,
            'name' => $name,
            'email' => $email,
            'password' => $tempPassword,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        File::put(
            storage_path('app/superadmin_provisioning_runtime.json'),
            json_encode([
                'status' => 'created',
                'user_id' => $user->id,
                'email' => $email,
                'username' => $username,
                'name' => $name,
                'must_change_password' => true,
                'temp_password' => $tempPassword,
                'generated_at' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
