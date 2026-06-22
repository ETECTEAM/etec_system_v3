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
            'password' => Hash::make('password'), // លេខសម្ងាត់គឺ: password
            'is_active' => true,
            'status' => UserStatus::Active ?? 'active',
        ]);

        // ផ្តល់ Role super_admin ទៅឱ្យគាត់
        // ដោយសារ User Model របស់អ្នកប្រើ dynamic guard វានឹងរត់ចូលទាំង web និង sanctum ដោយស្វ័យប្រវត្ត
        $superAdmin->assignRole('super_admin');

        // ២. បង្កើតគណនី Instructor គំរូ (តេស្តសាកល្បង)
        $instructor = User::create([
            'name' => 'John Doe (Instructor)',
            'email' => 'instructor@etec.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'status' => UserStatus::Active ?? 'active',
        ]);
        
        $instructor->assignRole('instructor');
    }
}