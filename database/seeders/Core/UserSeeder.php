<?php

namespace Database\Seeders\Core;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        User::truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $users = [
            ['name' => 'Super Admin',    'email' => 'superadmin@etec.com', 'role' => 'super_admin'],
            ['name' => 'Admin User',     'email' => 'admin@etec.com',      'role' => 'admin'],
            ['name' => 'Instructor User','email' => 'instructor@etec.com', 'role' => 'instructor'],
            ['name' => 'Student User',   'email' => 'student@etec.com',    'role' => 'student'],
            ['name' => 'Test Student',   'email' => 'teststudent@etec.com','role' => 'student'],
        ];

        foreach ($users as $data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make('password'),
                'role'     => $data['role'],
                'status'   => 'active',
            ]);

            $user->syncRoles([$data['role']]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}