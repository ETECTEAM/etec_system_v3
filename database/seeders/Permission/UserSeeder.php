<?php

namespace Database\Seeders\Permission;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => 'password',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => 'password',
                'role' => 'admin',
            ],
            [
                'name' => 'Instructor User',
                'email' => 'instructor@example.com',
                'password' => 'password',
                'role' => 'instructor',
            ],
            [
                'name' => 'Student User',
                'email' => 'student@example.com',
                'password' => 'password',
                'role' => 'student',
            ],
        ];

        foreach ($users as $defaultUser) {
            $user = User::firstOrCreate(
                ['email' => $defaultUser['email']],
                [
                    'name' => $defaultUser['name'],
                    'password' => Hash::make($defaultUser['password']),
                ]
            );

            $user->forceFill([
                'name' => $defaultUser['name'],
                'password' => Hash::make($defaultUser['password']),
            ])->save();

            $user->syncRoles([$defaultUser['role']]);
        }
    }
}
