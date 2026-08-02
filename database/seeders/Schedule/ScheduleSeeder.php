<?php

namespace Database\Seeders\Schedule;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {

        // Depends on ClassTypeSeeder / TermSeeder / TimeSeeder having already run
        // (DatabaseSeeder runs them before ScheduleSeeder), so we look the ids up by name instead of
        // hardcoding them.
        $scholarshipClassTypeId = DB::table('class_type')->where('type_name', 'Scholarship Class')->value('class_type_id');
        $physicalClassTypeId = DB::table('class_type')->where('type_name', 'Physical Class')->value('class_type_id');
        $onlineClassTypeId = DB::table('class_type')->where('type_name', 'Online Class')->value('class_type_id');
        $basicClassTypeId = DB::table('class_type')->where('type_name', 'Basic')->value('class_type_id');
        $monThuTermId = DB::table('terms')->where('term_name', 'Mon & Thu')->value('id');
        $satSunTermId = DB::table('terms')->where('term_name', 'Sat & Sun')->value('id');
        $monTueTermId = DB::table('terms')->where('term_name', 'Mon & Tue')->value('id');
        $wedThuTermId = DB::table('terms')->where('term_name', 'Wed & Thu')->value('id');
        $saturdayTermId = DB::table('terms')->where('term_name', 'Saturday')->value('id');
        $sundayTermId = DB::table('terms')->where('term_name', 'Sunday')->value('id');

        $findTimeId = fn (string $name) => DB::table('times')->where('time_name', $name)->value('id');

        $data = [
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
            // Physical Class - Mon & Thu
            [
                'class_type_id' => $physicalClassTypeId,
                'term_id' => $monThuTermId,
                'times' => [
                    $findTimeId('09:00 am - 10:30 am'),
                    $findTimeId('11:00 am - 12:15 pm'),
                    $findTimeId('12:30 pm - 01:45 pm'),
                    $findTimeId('02:00 pm - 3:15 pm'),
                    $findTimeId('03:30 pm - 05:00 pm'),
                    $findTimeId('06:00 pm - 07:15 pm'),
                    $findTimeId('07:15 pm - 8:30 pm'),
                ],
            ],
            // Physical Class - Sat & Sun
            [
                'class_type_id' => $physicalClassTypeId,
                'term_id' => $satSunTermId,
                'times' => [
                    $findTimeId('08:00 am - 11:00 am'),
                    $findTimeId('11:00 am - 01:30 pm'),
                    $findTimeId('02:00 pm - 05:00 pm'),
                ],
            ],
            // Online Class - Mon & Thu
            [
                'class_type_id' => $onlineClassTypeId,
                'term_id' => $monThuTermId,
                'times' => [
                    $findTimeId('09:00 am - 10:30 am'),
                    $findTimeId('11:00 am - 12:15 pm'),
                    $findTimeId('12:30 pm - 01:45 pm'),
                    $findTimeId('02:00 pm - 3:15 pm'),
                    $findTimeId('03:30 pm - 05:00 pm'),
                    $findTimeId('06:00 pm - 07:15 pm'),
                    $findTimeId('07:15 pm - 8:30 pm'),
                ],
            ],
            // Online Class - Sat & Sun
            [
                'class_type_id' => $onlineClassTypeId,
                'term_id' => $satSunTermId,
                'times' => [
                    $findTimeId('08:00 am - 11:00 am'),
                    $findTimeId('11:00 am - 01:30 pm'),
                    $findTimeId('02:00 pm - 05:00 pm'),
                ],
            ],
            // Basic - Mon & Tue
            [
                'class_type_id' => $basicClassTypeId,
                'term_id' => $monTueTermId,
                'times' => [
                    $findTimeId('09:00 am - 10:30 am'),
                    $findTimeId('11:00 am - 12:15 pm'),
                    $findTimeId('12:30 pm - 01:45 pm'),
                    $findTimeId('02:00 pm - 3:15 pm'),
                    $findTimeId('03:30 pm - 05:00 pm'),
                    $findTimeId('06:00 pm - 07:15 pm'),
                    $findTimeId('07:15 pm - 8:30 pm'),
                ],
            ],
            // Basic - Wed & Thu
            [
                'class_type_id' => $basicClassTypeId,
                'term_id' => $wedThuTermId,
                'times' => [
                    $findTimeId('09:00 am - 10:30 am'),
                    $findTimeId('11:00 am - 12:15 pm'),
                    $findTimeId('12:30 pm - 01:45 pm'),
                    $findTimeId('02:00 pm - 3:15 pm'),
                    $findTimeId('03:30 pm - 05:00 pm'),
                    $findTimeId('06:00 pm - 07:15 pm'),
                    $findTimeId('07:15 pm - 8:30 pm'),
                ],
            ],
            // Basic - Saturday
            [
                'class_type_id' => $basicClassTypeId,
                'term_id' => $saturdayTermId,
                'times' => [
                    $findTimeId('08:00 am - 11:00 am'),
                    $findTimeId('11:00 am - 01:30 pm'),
                    $findTimeId('02:00 pm - 05:00 pm'),
                ],
            ],
            // Basic - Sunday
            [
                'class_type_id' => $basicClassTypeId,
                'term_id' => $sundayTermId,
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