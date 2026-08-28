<?php

namespace Database\Seeders\Feature\Enroll;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\Room;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use App\Modules\Enroll\Services\StudentRegistrationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a spread of open classes with different start_date values so the
 * "Move to Another Class" modal's "Just started" badge, its start-info line,
 * and the just-started-first sort are all visible, plus one test student to
 * open the modal from.
 *
 * Idempotent: re-running it wipes its own previous data first (everything it
 * makes is titled "MoveTest ...", the student's phone is 098111222).
 *
 * Run manually (not part of db:seed):
 *   php artisan db:seed --class="Database\Seeders\Feature\Enroll\JustStartedClassSeeder"
 */
class JustStartedClassSeeder extends Seeder
{
    // A dedicated course so the class name IS the course name (like real
    // classes), while cleanup can still key off course_id without any risk of
    // touching real data.
    private const COURSE_TITLE = 'Move Modal Test';

    private const STUDENT_PHONE = '098111222';

    public function run(): void
    {
        $course = Course::firstOrCreate(
            ['title' => self::COURSE_TITLE],
            ['slug' => 'move-modal-test', 'status' => 'active'],
        );

        // Fall back to creating minimal lookup rows so this seeder also works on
        // a database that has no Course/Term/Time data yet.
        if (Term::query()->doesntExist()) {
            foreach (['Mon & Thu', 'Sat & Sun'] as $name) {
                Term::firstOrCreate(['term_name' => $name]);
            }
        }
        if (Time::query()->doesntExist()) {
            foreach (['09:00 - 10:30', '11:00 - 12:15', '02:00 pm - 03:15 pm'] as $name) {
                Time::firstOrCreate(['time_name' => $name]);
            }
        }

        $termIds = Term::query()->pluck('id')->all();
        $timeIds = Time::query()->pluck('id')->all();

        $teacherId = User::role('instructor')->value('id');
        $roomId = Room::query()->value('id');
        $classTypeId = ClassType::query()->value('class_type_id');

        $this->wipePrevious($course->id);

        $today = Carbon::today('Asia/Phnom_Penh');

        // start_date, expected treatment in the modal
        $specs = [
            ['label' => 'home class (student lives here)', 'start' => $today->copy()->subDays(10), 'home' => true],
            ['label' => 'started today',                   'start' => $today->copy()],
            ['label' => 'started 6 days ago',              'start' => $today->copy()->subDays(6)],
            ['label' => 'started 2 weeks ago',             'start' => $today->copy()->subDays(14)],
            ['label' => 'started 20 days ago (window edge)', 'start' => $today->copy()->subDays(20)],
            ['label' => 'started 5 weeks ago (not recent)', 'start' => $today->copy()->subDays(35)],
            ['label' => 'not started yet',                 'start' => $today->copy()->addDays(10)],
            ['label' => 'no start date',                   'start' => null],
        ];

        $homeClassId = null;

        foreach ($specs as $i => $spec) {
            $start = $spec['start'];

            // Class name = course title, like a real class. Cleanup keys off
            // course_id (see wipePrevious).
            $class = StudyClass::create([
                'title' => $course->title,
                'course_id' => $course->id,
                'lesson_id' => null,
                'teacher_id' => $teacherId,
                'room_id' => $roomId,
                'class_type_id' => $classTypeId,
                'term_id' => $termIds[$i % count($termIds)],
                'time_id' => $timeIds[$i % count($timeIds)],
                'status' => 'upcoming', // in the open set forSelect() returns
                'capacity' => 12,
                'price' => 100,
                'document_price' => 5,
                'enrollment_start_date' => $start?->copy()->subWeek(),
                'start_date' => $start,
                'end_date' => $start?->copy()->addMonths(2),
            ]);

            if ($spec['home'] ?? false) {
                $homeClassId = $class->id;
            }

            $this->command?->info(sprintf(
                '  class #%d  start %s  →  %s',
                $class->id,
                $start?->toDateString() ?? '(none)',
                $spec['label'],
            ));
        }

        $this->attachTestStudent($homeClassId, $course->id, $termIds[0], $timeIds[0]);

        $this->command?->info(sprintf('Seeded %d "%s" classes with staggered start dates.', count($specs), $course->title));
        $this->command?->info('Open the Class List, find "Move Modal Test Student", click the Move action, and check the modal:');
        $this->command?->info('  • classes started within 3 weeks get a green "Just started" badge and sort to the top');
        $this->command?->info('  • older / future / dateless ones get an amber / sky / slate badge instead');
    }

    private function attachTestStudent(?int $homeClassId, int $courseId, int $termId, int $timeId): void
    {
        if ($homeClassId === null) {
            return;
        }

        try {
            $registrations = app(StudentRegistrationService::class);

            $student = $registrations->createStudent(
                ['name' => 'Move Modal Test Student', 'gender' => 'male', 'phone' => self::STUDENT_PHONE],
                null,
                ['course_id' => $courseId, 'term_id' => $termId, 'time_id' => $timeId],
            );

            $registrations->createEnrollment([
                'study_class_id' => $homeClassId,
                'student_id' => $student->id,
                'enrollment_status' => 'active',
                'payment_status' => 'unpaid',
                'source' => 'walk_in',
                'enrolled_at' => now(),
            ]);

            $this->command?->info('Test student "Move Modal Test Student" enrolled in the home class.');
        } catch (\Throwable $e) {
            $this->command?->warn('Could not auto-enroll a test student ('.$e->getMessage().') — open the Move modal from any existing student instead.');
        }
    }

    private function wipePrevious(int $courseId): void
    {
        // study_classes -> student_enrollments is ON DELETE CASCADE, so dropping
        // the classes clears their enrollments too. Everything this seeder makes
        // belongs to its own dedicated course, so course_id is a safe key.
        StudyClass::query()->where('course_id', $courseId)->delete();

        // Legacy: earlier versions titled the rows "MoveTest N".
        StudyClass::query()->where('title', 'like', 'MoveTest %')->delete();

        DB::table('students')->where('phone', self::STUDENT_PHONE)->delete();
    }
}
