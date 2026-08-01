<?php

namespace Database\Seeders\Schedule;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {

        // insertOrIgnore keeps existing rows (e.g. real names from ClassTypeSeeder/TermSeeder/TimeSeeder)
        // untouched, and only fills in a placeholder if the id isn't seeded yet.
        DB::table('class_type')->insertOrIgnore([
            ['class_type_id' => 3, 'type_name' => 'IT Course Level 3'],
            ['class_type_id' => 4, 'type_name' => 'IT Course Level 4'],
        ]);

        foreach (range(1, 7) as $termId) {
            DB::table('terms')->insertOrIgnore(['id' => $termId, 'term_name' => "Term $termId"]);
        }

        foreach (range(1, 8) as $timeId) {
            DB::table('times')->insertOrIgnore(['id' => $timeId, 'time_name' => "Slot $timeId"]);
        }

        // Scholarship Class depends on ClassTypeSeeder / TermSeeder / TimeSeeder having already run
        // (DatabaseSeeder runs them before ScheduleSeeder), so we look the ids up by name instead of
        // hardcoding them.
        $scholarshipClassTypeId = DB::table('class_type')->where('type_name', 'Scholarship Class')->value('class_type_id');
        $monThuTermId = DB::table('terms')->where('term_name', 'Mon & Thu')->value('id');
        $satSunTermId = DB::table('terms')->where('term_name', 'Sat & Sun')->value('id');

        $findTimeId = fn (string $name) => DB::table('times')->where('time_name', $name)->value('id');

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
            // Scholarship Class - Mon & Thu
            [
                'class_type_id' => $scholarshipClassTypeId,
                'term_id' => $monThuTermId,
                'times' => [
                    $findTimeId('09:00 am - 11:00 am'),
                    $findTimeId('11:00 am - 01:30 pm'),
                    $findTimeId('03:30 pm - 05:30 pm'),
                    $findTimeId('05:30 pm - 07:30 pm'),
                ],
            ],
            // Scholarship Class - Sat & Sun
            [
                'class_type_id' => $scholarshipClassTypeId,
                'term_id' => $satSunTermId,
                'times' => [
                    $findTimeId('08:00 am - 11:00 am'),
                    $findTimeId('11:00 am - 01:30 pm'),
                    $findTimeId('02:00 pm - 05:00 pm'),
                ],
            ],
        ];

        foreach ($data as $item) {

            // 1. create schedule ONLY ONCE
            DB::table('schedules')->updateOrInsert(
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
                ->value('id'); // បើ primary key របស់ schedules ឈ្មោះ schedule_id សូមប្តូរត្រង់នេះ

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