<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Student / user domain
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'user.manage',
            'student.view',
            'student.create',
            'student.update',
            'student.delete',
            'student.manage',
            'blacklist_student.view',
            'blacklist_student.create',
            'blacklist_student.update',
            'blacklist_student.delete',
            'blacklist_student.manage',
            'role.view',
            'role.create',
            'role.update',
            'role.delete',
            'role.manage',
            'permission.view',
            'permission.create',
            'permission.update',
            'permission.delete',
            'permission.manage',

            // Academic structure
            'course.view',
            'course.create',
            'course.update',
            'course.delete',
            'course.manage',
            'course_category.view',
            'course_category.create',
            'course_category.update',
            'course_category.delete',
            'course_category.manage',
            'course_subcategory.view',
            'course_subcategory.create',
            'course_subcategory.update',
            'course_subcategory.delete',
            'course_subcategory.manage',
            'class.view',
            'class.create',
            'class.update',
            'class.delete',
            'class.manage',
            'class_type.view',
            'class_type.create',
            'class_type.update',
            'class_type.delete',
            'class_type.manage',
            'lesson.view',
            'lesson.create',
            'lesson.update',
            'lesson.delete',
            'lesson.manage',
            'building.view',
            'building.create',
            'building.update',
            'building.delete',
            'building.manage',
            'school_schedule.view',
            'school_schedule.create',
            'school_schedule.update',
            'school_schedule.delete',
            'school_schedule.manage',

            // Registration / promotion / business rules
            'register_student.view',
            'register_student.create',
            'register_student.update',
            'register_student.delete',
            'register_student.manage',
            'register_class.create',
            'promotion.view',
            'promotion.create',
            'promotion.update',
            'promotion.delete',
            'promotion.manage',
            'discount_rule.view',
            'discount_rule.create',
            'discount_rule.update',
            'discount_rule.delete',
            'discount_rule.manage',

            // Attendance / scoring
            'attendance.view',
            'attendance.create',
            'attendance.update',
            'attendance.delete',
            'attendance.manage',
            'attendance.track',
            'attendance_rule.view',
            'attendance_rule.create',
            'attendance_rule.update',
            'attendance_rule.delete',
            'attendance_rule.manage',
            'attendance_score.view',
            'attendance_score.create',
            'attendance_score.update',
            'attendance_score.delete',
            'attendance_score.manage',

            // Reporting / documents
            'certificate.view',
            'certificate.create',
            'certificate.update',
            'certificate.delete',
            'certificate.manage',
            'report.view',
            'report.create',
            'report.update',
            'report.delete',
            'report.manage',
            'card_employee.view',
            'card_employee.create',
            'card_employee.update',
            'card_employee.delete',
            'card_employee.manage',

            // Existing / compatibility permissions
            'enrollment.view',
            'enrollment.create',
            'enrollment.update',
            'enrollment.delete',

            // Student-facing actions
            'contact_school.create',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'sanctum',
            ]);
        }
    }
}
