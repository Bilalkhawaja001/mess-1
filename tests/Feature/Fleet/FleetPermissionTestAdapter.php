<?php

namespace Tests\Feature\Fleet;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

trait FleetPermissionTestAdapter
{
    protected function userWithFleetPermissions(array $permissions): User
    {
        $managerPermissions = collect($permissions)->contains(
            fn (string $permission) => str_ends_with($permission, '.manage')
                || str_ends_with($permission, '.approve')
                || str_ends_with($permission, '.export')
        );

        $role = $this->fleetTestRole($managerPermissions ? 'SUPER_ADMIN' : 'ADMIN', $permissions);

        return $this->fleetUserForRole($role, $managerPermissions ? 'fleet_manager' : 'fleet_viewer');
    }

    protected function userWithoutFleetPermissions(): User
    {
        $role = $this->fleetTestRole('DATA_ENTRY', []);

        return $this->fleetUserForRole($role, 'fleet_no_perm');
    }

    protected function fleetTestRole(string $roleCode, array $permissions): Role
    {
        $role = Role::query()->firstOrCreate(
            ['code' => $roleCode],
            ['name' => $roleCode.' Fleet Test Role', 'is_active' => true]
        );

        $permissionIds = collect($permissions)
            ->map(fn (string $code) => Permission::query()->firstOrCreate(
                ['code' => $code],
                ['name' => strtoupper(str_replace('.', ' ', $code))]
            )->id)
            ->all();

        $role->permissions()->sync($permissionIds);

        return $role;
    }

    protected function fleetUserForRole(Role $role, string $prefix): User
    {
        $suffix = str()->random(8);

        return User::query()->create([
            'username' => $prefix.'_'.$suffix,
            'name' => str($prefix)->replace('_', ' ')->title()->toString(),
            'email' => $prefix.'_'.$suffix.'@example.test',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }
}
