<?php

namespace Database\Seeders\Core;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Clear Spatie permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Make sure roles exist
        foreach (['super_admin', 'admin', 'instructor', 'student'] as $role) {
            Role::updateOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        $fixedUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@etec.com',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@etec.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Instructor User',
                'email' => 'instructor@etec.com',
                'role' => 'instructor',
            ],
        ];

        foreach ($fixedUsers as $fixedUser) {
            $user = User::updateOrCreate(
                ['email' => $fixedUser['email']],
                [
                    'name' => $fixedUser['name'],
                    'password' => Hash::make('password'),
                    'role' => $fixedUser['role'],
                    'status' => true,
                ]
            );

            // Also update Spatie role table: model_has_roles
            $user->syncRoles([$fixedUser['role']]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}