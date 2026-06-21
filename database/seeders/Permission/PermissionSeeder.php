<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // សម្អាត Cache របស់ Spatie ជាមុនសិនដើម្បីកុំឱ្យទើសសិទ្ធិចាស់
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // បញ្ជីឈ្មោះសិទ្ធិទាំងអស់នៅក្នុងប្រព័ន្ធ (អាចថែមថយតាមចិត្ត)
        $permissions = [
            // គ្រប់គ្រង Users
            'view-users', 'create-users', 'edit-users', 'delete-users',
            // គ្រប់គ្រង Classes
            'view-classes', 'create-classes', 'edit-classes', 'delete-classes',
            // គ្រប់គ្រង Terms & Times
            'view-terms', 'manage-terms',
            'view-times', 'manage-times',
        ];

        foreach ($permissions as $permission) {
            // បង្កើតសិទ្ធិសម្រាប់ទាំង web និង sanctum guard ដើម្បីកុំឱ្យជួប Error ថ្ងៃក្រោយ
            Permission::findOrCreate($permission, 'web');
            Permission::findOrCreate($permission, 'sanctum');
        }
    }
}