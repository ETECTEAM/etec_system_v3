<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['super_admin', 'admin', 'instructor', 'student'];

        foreach ($roles as $role) {
            // បង្កើត Role សម្រាប់ទាំង web និង sanctum guard ដូចគ្នា
            Role::findOrCreate($role, 'web');
            Role::findOrCreate($role, 'sanctum');
        }
    }
}