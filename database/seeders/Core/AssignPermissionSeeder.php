<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();

        $superAdmin = Role::where('name', 'super_admin')->where('guard_name', 'web')->first();
        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $instructor = Role::where('name', 'instructor')->where('guard_name', 'web')->first();
        $student = Role::where('name', 'student')->where('guard_name', 'web')->first();

        $superAdmin->syncPermissions($allPermissions);
        $admin->syncPermissions([]);
        $instructor->syncPermissions([]);
        $student->syncPermissions([]);
    }
}
