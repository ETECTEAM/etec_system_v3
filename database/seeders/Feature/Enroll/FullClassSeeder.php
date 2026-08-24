<?php

namespace Database\Seeders\Feature\Enroll;

use App\Models\Course;
use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\Room;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Modules\Website\Actions\RegisterStudentForSchedule;
use Illuminate\Database\Seeder;

/**
 * Makes every room and teacher unavailable, then runs one registration
 * through the real public-registration action so the "no room / no
 * instructor available" branch is visible immediately - no need to fill in
 * the public form by hand.
 *
 * Run manually (not part of the default db:seed run):
 *   php artisan db:seed --class="Database\Seeders\Feature\Enroll\FullClassSeeder"
 */
class FullClassSeeder extends Seeder
{
    private const TEST_PHONE = '099999999';

    private const OPEN_CLASS_STATUSES = ['upcoming', 'active', 'pre_end'];

    public function run(): void
    {
        Room::query()->update([
            'status' => 'occupied',
        ]);

        InstructorData::query()->update([
            'available_for_class' => false,
            'status' => false,
        ]);

        InstructorAvailability::query()->update([
            'is_active' => false,
        ]);

        $this->command?->info('All rooms are now occupied and all instructors are marked unavailable for class assignment.');

        $this->registerTestStudent();
    }

    // Picks a course/term/time combo with no existing open StudyClass, so
    // RegisterStudentForSchedule is forced into createClass() rather than
    // reusing an already-staffed/roomed class - only that path actually
    // exercises the "no room / no instructor" branch we just set up.
    private function registerTestStudent(): void
    {
        $combo = $this->findComboWithNoOpenClass();

        if ($combo === null) {
            $this->command?->warn('Could not find a course/term/time combo without an existing open class - skipped the test registration. Pick a course/term/time on the public registration form manually instead.');

            return;
        }

        [$courseId, $termId, $timeId] = $combo;

        $enrollment = app(RegisterStudentForSchedule::class)->handle([
            'name' => 'Seeder Test Student',
            'gender' => 'male',
            'phone' => self::TEST_PHONE,
            'course_id' => $courseId,
            'term_id' => $termId,
            'time_id' => $timeId,
        ]);

        if ($enrollment !== null) {
            $this->command?->warn("Test registration was assigned to class #{$enrollment->study_class_id} instead of going unassigned - that class must already have had a room/teacher before this seeder ran.");

            return;
        }

        $unassigned = StudentEnrollment::query()->where('enrollment_status', 'unassigned')->latest('id')->first();

        $this->command?->info("Unassigned enrollment created (id #{$unassigned?->id}, phone ".self::TEST_PHONE.') - check the dashboard notification bell or the Registrations tab on the Class List page to see it.');
    }

    private function findComboWithNoOpenClass(): ?array
    {
        $courseIds = Course::query()->where('status', 'active')->pluck('id');
        $termIds = Term::query()->pluck('id');
        $timeIds = Time::query()->pluck('id');

        foreach ($courseIds as $courseId) {
            foreach ($termIds as $termId) {
                foreach ($timeIds as $timeId) {
                    $hasOpenClass = StudyClass::query()
                        ->where('course_id', $courseId)
                        ->where('term_id', $termId)
                        ->where('time_id', $timeId)
                        ->whereIn('status', self::OPEN_CLASS_STATUSES)
                        ->exists();

                    if (! $hasOpenClass) {
                        return [$courseId, $termId, $timeId];
                    }
                }
            }
        }

        return null;
    }
}
