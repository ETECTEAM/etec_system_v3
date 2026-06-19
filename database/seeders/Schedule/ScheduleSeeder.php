<?php

namespace Database\Seeders\Schedule;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $data = [

            // PHP + Laravel
            [
                'class_type_id' => 3,
                'term_id' => 1,
                'times' => [1, 2, 3],
            ],

            // C++ Programming
            [
                'class_type_id' => 3,
                'term_id' => 3,
                'times' => [4, 5, 6],
            ],

            // Web Design + React
            [
                'class_type_id' => 3,
                'term_id' => 2,
                'times' => [2, 3],
            ],

            // Python + Flask
            [
                'class_type_id' => 4,
                'term_id' => 7,
                'times' => [7, 8],
            ],

            // UI/UX Design
            [
                'class_type_id' => 4,
                'term_id' => 4,
                'times' => [5],
            ],
        ];

        foreach ($data as $item) {

            // 1. create schedule ONLY ONCE
            $schedule = DB::table('schedules')->updateOrInsert(
                [
                    'class_type_id' => $item['class_type_id'],
                    'term_id'       => $item['term_id'],
                ],
                [
                    'class_type_id' => $item['class_type_id'],
                    'term_id'       => $item['term_id'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );

            // get schedule id
            $scheduleId = DB::table('schedules')
                ->where('class_type_id', $item['class_type_id'])
                ->where('term_id', $item['term_id'])
                ->value('id');

            // 2. insert MANY times
            foreach ($item['times'] as $timeId) {

                DB::table('schedule_time')->updateOrInsert(
                    [
                        'schedule_id' => $scheduleId,
                        'time_id'     => $timeId,
                    ],
                    [
                        'schedule_id' => $scheduleId,
                        'time_id'     => $timeId,
                    ]
                );
            }
        }
    }
}