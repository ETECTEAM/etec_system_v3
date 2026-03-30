<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        $user = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('123456'),
            ]
        );

        $user->assignRole('super_admin');
    }
}
