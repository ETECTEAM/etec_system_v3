<?php

namespace Database\Seeders\Schedule;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Depends on ClassTypeSeeder / TermSeeder / TimeSeeder having already run.
        // We still guard the lookups so a rerun fails with a useful message instead of a SQL error.
        $scholarshipClassTypeId = $this->requireClassTypeId('Scholarship Class');
        $physicalClassTypeId = $this->requireClassTypeId('Physical Class');
        $onlineClassTypeId = $this->requireClassTypeId('Online Class');
        $basicClassTypeId = $this->requireClassTypeId('Basic');
        $monThuTermId = $this->requireTermId('Mon & Thu');
        $satSunTermId = $this->requireTermId('Sat & Sun');
        $monTueTermId = $this->requireTermId('Mon & Tue');
        $wedThuTermId = $this->requireTermId('Wed & Thu');
        $saturdayTermId = $this->requireTermId('Saturday');
        $sundayTermId = $this->requireTermId('Sunday');

        $data = [
            // Scholarship Class - Mon & Thu
            [
                'class_type_id' => $scholarshipClassTypeId,
                'term_id' => $monThuTermId,
                'times' => [
                    '09:00 am - 11:00 am',
                    '11:00 am - 01:30 pm',
                    '03:30 pm - 05:30 pm',
                    '05:30 pm - 07:30 pm',
                ],
            ],
            // Scholarship Class - Sat & Sun
            [
                'class_type_id' => $scholarshipClassTypeId,
                'term_id' => $satSunTermId,
                'times' => [
                    '08:00 am - 11:00 am',
                    '11:00 am - 01:30 pm',
                    '02:00 pm - 05:00 pm',
                ],
            ],
            // Physical Class - Mon & Thu
            [
                'class_type_id' => $physicalClassTypeId,
                'term_id' => $monThuTermId,
                'times' => [
                    '09:00 am - 10:30 am',
                    '11:00 am - 12:15 pm',
                    '12:30 pm - 01:45 pm',
                    '02:00 pm - 3:15 pm',
                    '03:30 pm - 05:00 pm',
                    '06:00 pm - 07:15 pm',
                    '07:15 pm - 8:30 pm',
                ],
            ],
            // Physical Class - Sat & Sun
            [
                'class_type_id' => $physicalClassTypeId,
                'term_id' => $satSunTermId,
                'times' => [
                    '08:00 am - 11:00 am',
                    '11:00 am - 01:30 pm',
                    '02:00 pm - 05:00 pm',
                ],
            ],
            // Online Class - Mon & Thu
            [
                'class_type_id' => $onlineClassTypeId,
                'term_id' => $monThuTermId,
                'times' => [
                    '09:00 am - 10:30 am',
                    '11:00 am - 12:15 pm',
                    '12:30 pm - 01:45 pm',
                    '02:00 pm - 3:15 pm',
                    '03:30 pm - 05:00 pm',
                    '06:00 pm - 07:15 pm',
                    '07:15 pm - 8:30 pm',
                ],
            ],
            // Online Class - Sat & Sun
            [
                'class_type_id' => $onlineClassTypeId,
                'term_id' => $satSunTermId,
                'times' => [
                    '08:00 am - 11:00 am',
                    '11:00 am - 01:30 pm',
                    '02:00 pm - 05:00 pm',
                ],
            ],
            // Basic - Mon & Tue
            [
                'class_type_id' => $basicClassTypeId,
                'term_id' => $monTueTermId,
                'times' => [
                    '09:00 am - 10:30 am',
                    '11:00 am - 12:15 pm',
                    '12:30 pm - 01:45 pm',
                    '02:00 pm - 3:15 pm',
                    '03:30 pm - 05:00 pm',
                    '06:00 pm - 07:15 pm',
                    '07:15 pm - 8:30 pm',
                ],
            ],
            // Basic - Mon & Thu
            [
                'class_type_id' => $basicClassTypeId,
                'term_id' => $monThuTermId,
                'times' => [
                    '09:00 am - 10:30 am',
                    '11:00 am - 12:15 pm',
                    '12:30 pm - 01:45 pm',
                    '02:00 pm - 3:15 pm',
                    '03:30 pm - 05:00 pm',
                    '06:00 pm - 07:15 pm',
                    '07:15 pm - 8:30 pm',
                ],
            ],
            // Basic - Wed & Thu
            [
                'class_type_id' => $basicClassTypeId,
                'term_id' => $wedThuTermId,
                'times' => [
                    '09:00 am - 10:30 am',
                    '11:00 am - 12:15 pm',
                    '12:30 pm - 01:45 pm',
                    '02:00 pm - 3:15 pm',
                    '03:30 pm - 05:00 pm',
                    '06:00 pm - 07:15 pm',
                    '07:15 pm - 8:30 pm',
                ],
            ],
            // Basic - Saturday
            [
                'class_type_id' => $basicClassTypeId,
                'term_id' => $saturdayTermId,
                'times' => [
                    '08:00 am - 11:00 am',
                    '11:00 am - 01:30 pm',
                    '02:00 pm - 05:00 pm',
                ],
            ],
            // Basic - Sunday
            [
                'class_type_id' => $basicClassTypeId,
                'term_id' => $sundayTermId,
                'times' => [
                    '08:00 am - 11:00 am',
                    '11:00 am - 01:30 pm',
                    '02:00 pm - 05:00 pm',
                ],
            ],
            // Basic - Sat & Sun
            [
                'class_type_id' => $basicClassTypeId,
                'term_id' => $satSunTermId,
                'times' => [
                    '08:00 am - 11:00 am',
                    '11:00 am - 01:30 pm',
                    '02:00 pm - 05:00 pm',
                ],
            ],
        ];

        foreach ($data as $item) {
            // Create the schedule once, then attach all referenced times.
            DB::table('schedules')->updateOrInsert(
                [
                    'class_type_id' => $item['class_type_id'],
                    'term_id'       => $item['term_id'],
                ],
                [
                    'class_type_id' => $item['class_type_id'],
                    'term_id'       => $item['term_id'],
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]
            );

            $scheduleId = DB::table('schedules')
                ->where('class_type_id', $item['class_type_id'])
                ->where('term_id', $item['term_id'])
                ->value('id');

            foreach ($item['times'] as $timeName) {
                $timeId = $this->ensureTimeId($timeName, $now);
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

    private function requireClassTypeId(string $typeName): int
    {
        $id = DB::table('class_type')->where('type_name', $typeName)->value('class_type_id');

        if ($id === null) {
            throw new RuntimeException("ScheduleSeeder requires class type [{$typeName}] to exist.");
        }

        return (int) $id;
    }

    private function requireTermId(string $termName): int
    {
        $id = DB::table('terms')->where('term_name', $termName)->value('id');

        if ($id === null) {
            throw new RuntimeException("ScheduleSeeder requires term [{$termName}] to exist.");
        }

        return (int) $id;
    }

    private function ensureTimeId(string $timeName, Carbon $now): int
    {
        $id = DB::table('times')->where('time_name', $timeName)->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        return (int) DB::table('times')->insertGetId([
            'time_name' => $timeName,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
