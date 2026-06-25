<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AssignPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cache before syncing permissions.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Get all permission names from database.
        $allPermissions = Permission::pluck('name')->toArray();

        // Get or create roles.
        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $instructor = Role::firstOrCreate([
            'name' => 'instructor',
            'guard_name' => 'web',
        ]);

        $student = Role::firstOrCreate([
            'name' => 'student',
            'guard_name' => 'web',
        ]);

        $guest = Role::firstOrCreate([
            'name' => 'guest',
            'guard_name' => 'web',
        ]);

        // super_admin gets all permissions.
        $superAdmin->syncPermissions($allPermissions);

        // Other roles start empty.
        // Super admin can assign permissions later from UI.
        $admin->syncPermissions([]);
        $instructor->syncPermissions([]);
        $student->syncPermissions([]);
        $guest->syncPermissions([]);

        // Clear cache after syncing permissions.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}