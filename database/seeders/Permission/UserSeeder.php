<?php

namespace Database\Seeders\Permission;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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
            [
                'name' => 'Student User',
                'email' => 'student@etec.com',
                'role' => 'student',
            ],
        ];

        foreach ($fixedUsers as $fixedUser) {
            $user = User::firstOrCreate(
                ['email' => $fixedUser['email']],
                [
                    'name' => $fixedUser['name'],
                    'password' => Hash::make('password'),
                ]
            );

            $user->forceFill([
                'name' => $fixedUser['name'],
                'password' => Hash::make('password'),
            ])->save();

            $user->syncRoles([$fixedUser['role']]);
        }

        $roles = Role::query()
            ->where('guard_name', 'sanctum')
            ->whereIn('name', ['admin', 'instructor', 'student'])
            ->pluck('name')
            ->all();

        User::factory(100)
            ->create()
            ->each(function (User $user) use ($roles): void {
                $user->syncRoles([$roles[array_rand($roles)]]);
            });
    }
}
