<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Clear old Spatie permission/role cache before seeding roles.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Default system roles.
        $roles = [
            'super_admin',
            'admin',
            'instructor',
            'student',
            'guest',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        // Clear cache again after creating roles.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}