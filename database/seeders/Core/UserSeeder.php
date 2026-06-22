<?php

namespace Database\Seeders\Core;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
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
            $user = User::firstOrCreate(
                ['email' => $fixedUser['email']],
                [
                    'name' => $fixedUser['name'],
                    'password' => Hash::make('  '),
                ]
            );

            $user->forceFill([
                'name' => $fixedUser['name'],
                'password' => Hash::make('password'),
            ])->save();

            $user->syncRoles([$fixedUser['role']]);
        }

    }
}
