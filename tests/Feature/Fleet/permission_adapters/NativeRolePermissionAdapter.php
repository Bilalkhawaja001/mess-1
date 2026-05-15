<?php

namespace Tests\Feature\Fleet\PermissionAdapters;

class NativeRolePermissionAdapter implements FleetPermissionAdapterInterface
{
    public function supports(object $testCase, object $user): bool
    {
        return method_exists($user, 'permissions');
    }

    public function grant(object $testCase, object $user, array $permissions): object
    {
        foreach ($permissions as $permission) {
            $user->permissions()->firstOrCreate(['name' => $permission]);
        }

        return $user->refresh();
    }
}
