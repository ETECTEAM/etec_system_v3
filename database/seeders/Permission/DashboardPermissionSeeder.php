<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DashboardPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'instructor_profile.view',
            'instructor_profile.update',
            'work_schedule.view',
            'work_schedule.create',
            'work_schedule.update',
            'work_schedule.delete',
            'work_schedule.manage',
            'instructor_schedule_block.view',
            'instructor_schedule_block.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::where('name', 'super_admin')->where('guard_name', 'web')->first();
        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $instructor = Role::where('name', 'instructor')->where('guard_name', 'web')->first();
        $student = Role::where('name', 'student')->where('guard_name', 'web')->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        if ($admin) {
            $admin->givePermissionTo(['dashboard.view']);
        }

        if ($instructor) {
            $instructor->givePermissionTo(['dashboard.view', 'instructor_profile.view', 'instructor_profile.update']);
        }

        if ($student) {
            $student->givePermissionTo(['dashboard.view']);
        }
    }
}
