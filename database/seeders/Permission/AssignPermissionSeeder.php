<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::pluck('name')->toArray();

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $instructor = Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'sanctum']);
        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'sanctum']);

        // super_admin → ALL permissions
        $superAdmin->syncPermissions($allPermissions);

        // Other roles start empty so permissions can be assigned later by super_admin.
        $admin->syncPermissions([]);
        $instructor->syncPermissions([]);
        $student->syncPermissions([]);
    }
}
