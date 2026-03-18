<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['code' => 'SUPER_ADMIN', 'name' => 'Super Admin'],
            ['code' => 'ADMIN', 'name' => 'Admin'],
            ['code' => 'DATA_ENTRY', 'name' => 'Data Entry'],
            ['code' => 'AUDITOR', 'name' => 'Auditor'],
            ['code' => 'MEMBER', 'name' => 'Member'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['code' => $role['code']],
                ['name' => $role['name'], 'is_active' => true]
            );
        }
    }
}
