<?php

namespace Database\Seeders\Permission;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\InstructorData;
use App\Enums\UserStatus;
use App\Modules\Instructor\Services\InstructorService;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@etec.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'status' => 'active'],
        );

        $superAdmin->assignRole('super_admin');

        $instructor1 = User::firstOrCreate(
            ['email' => 'instructor@etec.com'],
            ['name' => 'John Doe (Instructor)', 'password' => Hash::make('password'), 'status' => 'active'],
        );

        $instructor1->assignRole('instructor');

        InstructorData::firstOrCreate(
            ['user_id' => $instructor1->id],
            [
                'full_name' => 'John Doe (Instructor)',
                'instructor_code' => InstructorService::generateInstructorCode(),
                'phone' => '012345678',
                'employment_type' => 'full_time',
            ],
        );

        $instructor2 = User::firstOrCreate(
            ['email' => 'instructor2@etec.com'],
            ['name' => 'Jane Smith (Instructor)', 'password' => Hash::make('password'), 'status' => 'active'],
        );

        $instructor2->assignRole('instructor');

        InstructorData::firstOrCreate(
            ['user_id' => $instructor2->id],
            [
                'full_name' => 'Jane Smith (Instructor)',
                'instructor_code' => InstructorService::generateInstructorCode(),
                'phone' => '012345679',
                'employment_type' => 'part_time',
            ],
        );

        $student1 = User::firstOrCreate(
            ['email' => 'alice@etec.com'],
            ['name' => 'Alice Student', 'password' => Hash::make('password'), 'status' => 'active'],
        );

        $student1->assignRole('student');

        $student2 = User::firstOrCreate(
            ['email' => 'bob@etec.com'],
            ['name' => 'Bob Student', 'password' => Hash::make('password'), 'status' => 'active'],
        );

        $student2->assignRole('student');
    }
}
