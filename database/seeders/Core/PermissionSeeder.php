<?php

namespace Database\Seeders\Core;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $actions = [
            'view',
            'create',
            'update',
            'delete',
            'manage',
        ];

        $modules = [
            // Enrollment
            'enroll',
            'enrollment',

            // User / RBAC
            'user',
            'role',
            'permission',

            // Building structure
            'room',
            'floor',
            'building',

            // Course structure
            'course',
            'skill',
            'track',
            'course_category',
            'course_subcategory',
            'category',
            'subcategory',

            // Time / term / schedule
            'time',
            'term',
            'schedule',
            'school_schedule',

            // Academic
            'certificate',
            'promotion',
            'class',
            'class_type',
            'lesson',
            'student',
            'blacklist_student',

            // Attendance
            'attendance',
            'attendance_rule',
            'attendance_score',

            // Registration / business rules
            'register_student',
            'discount_rule',

            // Report / document
            'report',
            'card_employee',
        ];

        // Declare empty array to hold permissions
        $permissions = [];

        // Generate permissions based on modules and actions
        // example: 'user.view', 'user.create', 'user.update', 'user.delete', 'user.manage'
        foreach ($modules as $module) { // user, role...
            foreach ($actions as $action) { // user.view, user.create, user.update, user.delete, user.manage
                $permissions[] = "{$module}.{$action}";
            }
        }

        // Add special permissions that do not follow normal CRUD pattern.
        $permissions = array_merge($permissions, [
            // Allow user to register student into class
            'register_class.create',

            // Allow user to track attendance
            'attendance.track',

            // Allow student/user to contact school
            'contact_school.create',
        ]);

        // Remove duplicate permissions by using array_unique().
        // Then create each permission in database.
        foreach (array_unique($permissions) as $permission) {
            Permission::firstOrCreate([
                // Permission name, example: user.view
                'name' => $permission,

                // Guard name for web authentication
                'guard_name' => 'web',
            ]);
        }

        // Clear Spatie permission cache.
        // This makes sure Laravel uses the latest permissions.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}