<?php

namespace Tests\Feature\Fleet\PermissionAdapters;

class SpatiePermissionAdapter implements FleetPermissionAdapterInterface
{
    public function supports(object $testCase, object $user): bool
    {
        return class_exists(\Spatie\Permission\Models\Permission::class) && method_exists($user, 'givePermissionTo');
    }

    public function grant(object $testCase, object $user, array $permissions): object
    {
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission);
            $user->givePermissionTo($permission);
        }

        return $user->refresh();
    }
}
