<?php

namespace Database\Seeders\Core;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $users = [
            ['name' => 'Super Admin',    'email' => 'superadmin@etec.com'],
            ['name' => 'Admin User',     'email' => 'admin@etec.com'],
            ['name' => 'Instructor User','email' => 'instructor@etec.com'],
            ['name' => 'Student User',   'email' => 'student@etec.com'],
            ['name' => 'Test Student',   'email' => 'teststudent@etec.com'],
        ];

        $roles = [
            'superadmin@etec.com' => 'super_admin',
            'admin@etec.com'      => 'admin',
            'instructor@etec.com' => 'instructor',
            'student@etec.com'    => 'student',
            'teststudent@etec.com'=> 'student',
        ];

        foreach ($users as $data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make('password'),
            ]);

            $user->syncRoles([$roles[$data['email']]]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}