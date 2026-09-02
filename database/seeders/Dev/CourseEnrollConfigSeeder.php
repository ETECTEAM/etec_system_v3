<?php

namespace Database\Seeders\Dev;

use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dev-only. Opens enrollment for EVERY course on Physical Class slots only,
 * with flat pricing, so Enrollment Management > Course Enroll Config (and the
 * public register flow) has something to work against without hand-toggling
 * every course.
 *
 * Per course:
 *   - course-wide row (schedule_id + time_id NULL): status = open,
 *     start_date = NULL, unit_price = 100, course_price = 89,
 *     document_price = 5. This is the master switch + the charged price.
 *   - one open availability row per (Physical Class schedule, time) slot
 *     (schedule = class type + term). These are $0 toggles by design.
 *   - every non-Physical availability row deleted, so "Physical only" still
 *     holds on a re-run.
 *
 * "Basic IT" gets enroll_order = 1 so it sorts first on the register list;
 * every other course keeps enroll_order NULL and falls back to title sort.
 *
 * Idempotent: rows are keyed by (course_id, schedule_id, time_id).
 */
class CourseEnrollConfigSeeder extends Seeder
{
    private const UNIT_PRICE = 100;

    private const COURSE_PRICE = 89;

    private const DOCUMENT_PRICE = 5;

    private const PHYSICAL_CLASS = 'Physical Class';

    private const FIRST_COURSE_TITLE = 'Basic IT';

    public function run(): void
    {
        $courses = Course::query()->get(['id', 'title']);

        if ($courses->isEmpty()) {
            $this->command?->warn('CourseEnrollConfigSeeder: no courses - run CourseSeeder first. Skipped.');

            return;
        }

        // Physical Class schedules (class type + term) with their time slots.
        $physicalSchedules = Schedule::query()
            ->whereHas('classType', fn ($query) => $query->where('type_name', self::PHYSICAL_CLASS))
            ->with(['times:id'])
            ->get();

        if ($physicalSchedules->isEmpty()) {
            $this->command?->warn('CourseEnrollConfigSeeder: no "Physical Class" schedules - run ScheduleSeeder first. Skipped.');

            return;
        }

        $physicalScheduleIds = $physicalSchedules->pluck('id');

        DB::transaction(function () use ($courses, $physicalSchedules, $physicalScheduleIds): void {
            foreach ($courses as $course) {
                // 1. Course-wide master + pricing row.
                CourseEnrollConfig::query()->updateOrCreate(
                    ['course_id' => $course->id, 'schedule_id' => null, 'time_id' => null],
                    [
                        'status' => 'open',
                        'start_date' => null,
                        'unit_price' => self::UNIT_PRICE,
                        'course_price' => self::COURSE_PRICE,
                        'selected_price_type' => CourseEnrollConfig::PRICE_TYPE_COURSE,
                        'document_price' => self::DOCUMENT_PRICE,
                        'max_classes' => null,
                    ],
                );

                // 2. Drop availability rows for any non-Physical class type.
                CourseEnrollConfig::query()
                    ->where('course_id', $course->id)
                    ->whereNotNull('schedule_id')
                    ->whereNotIn('schedule_id', $physicalScheduleIds)
                    ->delete();

                // 3. One open row per Physical Class (schedule, time) slot.
                foreach ($physicalSchedules as $schedule) {
                    foreach ($schedule->times as $time) {
                        CourseEnrollConfig::query()->updateOrCreate(
                            [
                                'course_id' => $course->id,
                                'schedule_id' => $schedule->id,
                                'time_id' => $time->id,
                            ],
                            [
                                'status' => 'open',
                                'start_date' => null,
                                'unit_price' => 0,
                                'course_price' => 0,
                                'selected_price_type' => CourseEnrollConfig::PRICE_TYPE_COURSE,
                                'document_price' => 0,
                                'max_classes' => null,
                            ],
                        );
                    }
                }
            }
        });

        // 4. Basic IT first on the register list.
        $ordered = Course::query()
            ->where('title', self::FIRST_COURSE_TITLE)
            ->update(['enroll_order' => 1]);

        if ($ordered === 0) {
            $this->command?->warn(sprintf('CourseEnrollConfigSeeder: no course titled "%s" - enroll_order left untouched.', self::FIRST_COURSE_TITLE));
        }

        $slotCount = $physicalSchedules->sum(fn (Schedule $schedule) => $schedule->times->count());

        $this->command?->info(sprintf(
            'CourseEnrollConfigSeeder: %d courses opened on %d Physical Class slots (unit $%d / course $%d, start_date null).',
            $courses->count(),
            $slotCount,
            self::UNIT_PRICE,
            self::COURSE_PRICE,
        ));
    }
}
