<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $allPermissions = Permission::pluck('name')->toArray();

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $instructor = Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'sanctum']);


        // super_admin → ALL permissions
        $superAdmin->syncPermissions($allPermissions);

        // admin → manage most (no role/permission control)
        $admin->syncPermissions([
            // user
            'user.view',
            'user.create',
            'user.update',

            // course
            'course.view',
            'course.create',
            'course.update',
            'course.delete',

            // category
            'course_category.view',
            'course_category.create',
            'course_category.update',

            'course_subcategory.view',
            'course_subcategory.create',
            'course_subcategory.update',

            // enrollment
            'enrollment.view',
            'enrollment.create',
            'enrollment.update',
        ]);

        // instructor → limited access
        $instructor->syncPermissions([
            'course.view',
            'course.update',

            'course_category.view',
            'course_subcategory.view',

            'enrollment.view',
        ]);
    }
}
