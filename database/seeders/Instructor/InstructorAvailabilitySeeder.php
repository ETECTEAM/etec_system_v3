<?php

namespace Database\Seeders\Instructor;

use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class InstructorAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        InstructorAvailability::truncate();
        InstructorData::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $instructors = User::role('instructor')->get();

        if ($instructors->isEmpty()) {
            $sampleUsers = [
                ['name' => 'Instructor A', 'email' => 'instructor_a@etec.com'],
                ['name' => 'Instructor B', 'email' => 'instructor_b@etec.com'],
                ['name' => 'Instructor C', 'email' => 'instructor_c@etec.com'],
                ['name' => 'Instructor D', 'email' => 'instructor_d@etec.com'],
            ];

            foreach ($sampleUsers as $data) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make('password'),
                ]);
                $user->assignRole('instructor');
                $instructors->push($user);
            }
        }

        $instructorConfigs = [
            [
                'full_name' => 'Instructor A',
                'instructor_code' => 'INS001',
                'phone' => '012345678',
                'employment_type' => 'full_time',
                'shift_group' => 'morning_afternoon',
                'user_index' => 0,
            ],
            [
                'full_name' => 'Instructor B',
                'instructor_code' => 'INS002',
                'phone' => '012345679',
                'employment_type' => 'full_time',
                'shift_group' => 'morning_evening',
                'user_index' => min(1, $instructors->count() - 1),
            ],
            [
                'full_name' => 'Instructor C',
                'instructor_code' => 'INS003',
                'phone' => '012345680',
                'employment_type' => 'full_time',
                'shift_group' => 'afternoon_evening_11',
                'user_index' => min(2, $instructors->count() - 1),
            ],
            [
                'full_name' => 'Instructor D',
                'instructor_code' => 'INS004',
                'phone' => '012345681',
                'employment_type' => 'full_time',
                'shift_group' => 'afternoon_evening_1230',
                'user_index' => min(3, $instructors->count() - 1),
            ],
            [
                'full_name' => 'Instructor E',
                'instructor_code' => 'INS005',
                'phone' => '012345682',
                'employment_type' => 'part_time',
                'shift_group' => 'weekend_morning',
                'user_index' => min(4, $instructors->count() - 1),
            ],
            [
                'full_name' => 'Instructor F',
                'instructor_code' => 'INS006',
                'phone' => '012345683',
                'employment_type' => 'part_time',
                'shift_group' => 'weekend_afternoon',
                'user_index' => min(5, $instructors->count() - 1),
            ],
        ];

        $shiftPatterns = [
            'morning_afternoon' => [
                'days' => [1, 2, 3, 4, 5],
                'slots' => [
                    ['period' => 'daytime', 'start' => '08:00', 'end' => '17:00'],
                ],
            ],
            'morning_evening' => [
                'days' => [1, 2, 3, 4, 5],
                'slots' => [
                    ['period' => 'morning', 'start' => '08:00', 'end' => '12:00'],
                    ['period' => 'evening', 'start' => '17:00', 'end' => '20:30'],
                ],
            ],
            'afternoon_evening_11' => [
                'days' => [1, 2, 3, 4, 5],
                'slots' => [
                    ['period' => 'afternoon_evening', 'start' => '11:00', 'end' => '20:30'],
                ],
            ],
            'afternoon_evening_1230' => [
                'days' => [1, 2, 3, 4, 5],
                'slots' => [
                    ['period' => 'afternoon_evening', 'start' => '12:30', 'end' => '20:30'],
                ],
            ],
            'weekend_morning' => [
                'days' => [6, 7],
                'slots' => [
                    ['period' => 'morning', 'start' => '08:00', 'end' => '13:30'],
                ],
            ],
            'weekend_afternoon' => [
                'days' => [6, 7],
                'slots' => [
                    ['period' => 'afternoon', 'start' => '11:00', 'end' => '17:00'],
                ],
            ],
        ];

        foreach ($instructorConfigs as $index => $config) {
            $user = $instructors->values()->get($config['user_index']);

            $instructorData = InstructorData::create([
                'user_id' => $user->id,
                'full_name' => $config['full_name'],
                'instructor_code' => $config['instructor_code'],
                'phone' => $config['phone'],
                'employment_type' => $config['employment_type'],
                'shift_group' => $config['shift_group'],
            ]);

            $pattern = $shiftPatterns[$config['shift_group']];

            foreach ($pattern['days'] as $day) {
                foreach ($pattern['slots'] as $slot) {
                    InstructorAvailability::create([
                        'instructor_id' => $instructorData->id,
                        'day_of_week' => $day,
                        'employment_type' => $config['employment_type'],
                        'shift_group' => $config['shift_group'],
                        'period' => $slot['period'],
                        'start_time' => $slot['start'],
                        'end_time' => $slot['end'],
                    ]);
                }
            }
        }
    }
}
