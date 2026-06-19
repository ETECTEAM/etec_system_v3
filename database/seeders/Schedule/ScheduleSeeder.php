<?php

namespace Database\Seeders\Schedule;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schedules = [

            // =========================
            // PHP + Laravel (Class Type = 3)
            // Mon - Tue (Term = 1)
            // =========================
            [
                'class_type_id' => 3,
                'term_id'       => 1,
                'time_id'       => 1, // 09:00 - 10:30
            ],
            [
                'class_type_id' => 3,
                'term_id'       => 1,
                'time_id'       => 2, // 11:00 - 12:15
            ],
            [
                'class_type_id' => 3,
                'term_id'       => 1,
                'time_id'       => 3, // 12:30 - 01:45
            ],

            // =========================
            // C++ Programming (Class Type = 3)
            // Mon - Thur (Term = 3)
            // =========================
            [
                'class_type_id' => 3,
                'term_id'       => 3,
                'time_id'       => 4,
            ],
            [
                'class_type_id' => 3,
                'term_id'       => 3,
                'time_id'       => 5,
            ],
            [
                'class_type_id' => 3,
                'term_id'       => 3,
                'time_id'       => 6,
            ],

            // =========================
            // Web Design + React.js (Class Type = 3)
            // Wed - Thur (Term = 2)
            // =========================
            [
                'class_type_id' => 3,
                'term_id'       => 2,
                'time_id'       => 2,
            ],
            [
                'class_type_id' => 3,
                'term_id'       => 2,
                'time_id'       => 3,
            ],

            // =========================
            // Python + Flask (Weekend Class = 4)
            // Sat - Sun (Term = 7)
            // =========================
            [
                'class_type_id' => 4,
                'term_id'       => 7,
                'time_id'       => 7,
            ],
            [
                'class_type_id' => 4,
                'term_id'       => 7,
                'time_id'       => 8,
            ],

            // =========================
            // UI/UX Design (Figma)
            // Friday (Term = 4)
            // =========================
            [
                'class_type_id' => 4,
                'term_id'       => 4,
                'time_id'       => 5,
            ],
        ];

        foreach ($schedules as $schedule) {
            DB::table('schedules')->updateOrInsert(
                [
                    'class_type_id' => $schedule['class_type_id'],
                    'term_id'       => $schedule['term_id'],
                    'time_id'       => $schedule['time_id'],
                ],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}