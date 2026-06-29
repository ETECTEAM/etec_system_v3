<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ១. បង្កើតគណនី Super Admin លំនាំដើម
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@etec.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $superAdmin->assignRole('super_admin');

        $instructor = User::create([
            'name' => 'John Doe (Instructor)',
            'email' => 'instructor@etec.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $instructor->assignRole('instructor');
    }
}