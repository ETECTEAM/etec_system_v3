<?php

namespace Database\Seeders\Dev;

use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\Schedule;
use App\Modules\Enroll\Queries\GetCourseClassSchedules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dev-only. Opens enrollment for EVERY course on the slots of the Class Type
 * its track is mapped to (course_tracks.class_type_id) - falling back to the
 * default Physical / Scholarship / Online set for an unmapped track - so
 * Enrollment Management > Course Enroll Config (and the public register flow)
 * has something to work against without hand-toggling every course.
 *
 * Per course:
 *   - course-wide row (schedule_id + time_id NULL): status = open,
 *     start_date = NULL, unit_price = 100, course_price = 89,
 *     document_price = 5. This is the master switch + the charged price.
 *   - one open availability row per (schedule, time) slot of the course's
 *     resolved Class Type(s). These are $0 toggles by design.
 *   - every availability row outside that Class Type set deleted, so the
 *     mapping still holds on a re-run.
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

    private const FIRST_COURSE_TITLE = 'Basic IT';

    public function run(): void
    {
        $classSchedules = app(GetCourseClassSchedules::class);
        $courses = Course::query()->with('track')->get();

        if ($courses->isEmpty()) {
            $this->command?->warn('CourseEnrollConfigSeeder: no courses - run CourseSeeder first. Skipped.');

            return;
        }

        // Whole schedule tree once, indexed by class_type_id.
        $schedulesByType = Schedule::query()
            ->with(['times:id'])
            ->get()
            ->groupBy('class_type_id');

        if ($schedulesByType->isEmpty()) {
            $this->command?->warn('CourseEnrollConfigSeeder: no schedules - run ScheduleSeeder first. Skipped.');

            return;
        }

        $openedSlots = 0;

        DB::transaction(function () use ($courses, $schedulesByType, $classSchedules, &$openedSlots): void {
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

                // Schedules of the Class Type(s) this course resolves to.
                $targetSchedules = $classSchedules->classTypeIdsForCourse($course)
                    ->flatMap(fn (int $typeId) => $schedulesByType->get($typeId, collect()))
                    ->values();
                $targetScheduleIds = $targetSchedules->pluck('id');

                // 2. Drop availability rows outside that Class Type set.
                CourseEnrollConfig::query()
                    ->where('course_id', $course->id)
                    ->whereNotNull('schedule_id')
                    ->whereNotIn('schedule_id', $targetScheduleIds)
                    ->delete();

                // 3. One open row per (schedule, time) slot of the resolved Class Type.
                foreach ($targetSchedules as $schedule) {
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
                        $openedSlots++;
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

        $this->command?->info(sprintf(
            'CourseEnrollConfigSeeder: %d courses opened on %d class-type slots (unit $%d / course $%d, start_date null).',
            $courses->count(),
            $openedSlots,
            self::UNIT_PRICE,
            self::COURSE_PRICE,
        ));
    }
}
