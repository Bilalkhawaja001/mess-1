<?php

namespace Tests\Feature\Fleet\PermissionAdapters;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomTablePermissionAdapter implements FleetPermissionAdapterInterface
{
    public function supports(object $testCase, object $user): bool
    {
        return Schema::hasTable('permissions') && Schema::hasTable('permission_user');
    }

    public function grant(object $testCase, object $user, array $permissions): object
    {
        foreach ($permissions as $permission) {
            $permissionId = DB::table('permissions')->where('name', $permission)->value('id');
            if (!$permissionId) {
                $permissionId = DB::table('permissions')->insertGetId(['name' => $permission, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('permission_user')->updateOrInsert(['user_id' => $user->getKey(), 'permission_id' => $permissionId], ['created_at' => now(), 'updated_at' => now()]);
        }

        return $user->refresh();
    }
}
