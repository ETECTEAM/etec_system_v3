<?php

namespace Database\Seeders\Feature\Attendance;

use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\Course;
use App\Models\InstructorData;
use App\Models\Room;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use App\Modules\Instructor\Services\InstructorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds Case 1 of docs/instructor-attendance-block-proposal.md so it can be exercised
 * end-to-end without waiting for a real class time / the grace period. Both classes
 * use real rows from the existing course catalog (title mirrors the course, same as a
 * class created the normal way) rather than a synthetic "[AttBlock Test]" course:
 *
 *   1. "Missed" scenario  today's ClassSession = pending, scheduled 2 hours ago (well
 *                          past grace), 0 students tracked - running
 *                          `php artisan attendance:auto-record` flips it to
 *                          pre_attendance AND should create an
 *                          instructor_attendance_blocks row for this instructor.
 *   2. "Other" scenario   today's ClassSession = pending, scheduled 5 minutes ago
 *                          (inside the grace window) - completely unrelated to the
 *                          missed class, used to prove the block reaches every class
 *                          the instructor teaches, not just the offending one.
 *
 * Both classes are status=active and owned by the same, dedicated test instructor.
 *
 * Idempotent: re-running wipes its own data first (every class owned by the dedicated
 * test instructor, students with phone 0966000xx, and any leftover
 * instructor_attendance_blocks row for that instructor). Not part of db:seed.
 *
 * Run:
 *   php artisan db:seed --class="Database\Seeders\Feature\Attendance\InstructorAttendanceBlockTestSeeder"
 *   php artisan attendance:auto-record
 */
class InstructorAttendanceBlockTestSeeder extends Seeder
{
    private const STUDENT_PHONE_PREFIX = '0966000';

    // Must be @etec.com - LoginWebRequest rejects every other domain.
    private const INSTRUCTOR_EMAIL = 'attblock.instructor@etec.com';

    public function run(): void
    {
        $refs = $this->resolveReferences();

        $this->wipePrevious();

        $instructor = User::query()->updateOrCreate(
            ['email' => self::INSTRUCTOR_EMAIL],
            [
                'name' => 'AttBlock Test Instructor',
                'password' => bcrypt('password'),
                'status' => 'active',
                'role' => 'instructor',
            ],
        );

        if (! $instructor->hasRole('instructor')) {
            $instructor->assignRole('instructor');
        }

        // The instructor dashboard shows a "complete your profile" wall instead of any
        // classes until this row exists (see resources/js/pages/backend/InstructorDashboard.vue).
        $instructorData = InstructorData::query()->firstOrNew(['user_id' => $instructor->id]);
        $instructorData->fill([
            'full_name' => $instructor->name,
            'phone' => '099'.str_pad((string) $instructor->id, 7, '0', STR_PAD_LEFT),
            'employment_type' => 'full_time',
            'available_for_class' => true,
            'status' => true,
        ]);

        if (! $instructorData->exists) {
            $instructorData->instructor_code = InstructorService::generateInstructorCode();
        }

        $instructorData->save();

        $today = Carbon::today('Asia/Phnom_Penh');
        $now = Carbon::now('Asia/Phnom_Penh');

        // Distinct time_id per scenario - same instructor, same term/day, so a shared
        // time_id would mean two classes at the identical slot, which
        // InstructorAssignmentAvailability::hasConflictingClass() rejects for any class
        // created the normal way. This seeder writes directly to study_classes and
        // skips that check, so it has to keep the two slots apart itself.
        [$timeIdA, $timeIdB] = $refs['time_ids'];

        // Two different real courses from the catalog (not a synthetic one made up
        // for this seeder) - see the comment on resolveReferences() for why.
        [$courseIdA, $courseIdB] = $refs['course_ids'];

        // label, time_id, course_id, scheduled_start, scheduled_end
        $scenarios = [
            ['label' => 'Missed', 'time_id' => $timeIdA, 'course_id' => $courseIdA, 'start' => $now->copy()->subHours(2), 'end' => $now->copy()->subMinutes(30)],
            ['label' => 'Other', 'time_id' => $timeIdB, 'course_id' => $courseIdB, 'start' => $now->copy()->subMinutes(5), 'end' => $now->copy()->addMinutes(85)],
        ];

        $phoneCounter = 1;

        foreach ($scenarios as $scenario) {
            $phoneCounter = $this->makeScenario($instructor, $refs, $scenario, $today, $now, $phoneCounter);
        }

        $this->command?->info('');
        $this->command?->info(sprintf('Seeded 2 classes for instructor "%s" (id %d, email %s, password "password").', $instructor->name, $instructor->id, $instructor->email));
        $this->command?->info('  "Missed" scenario -> run `php artisan attendance:auto-record`, session becomes pre_attendance and blocks this instructor.');
        $this->command?->info('  "Other" scenario  -> untouched class; after the command above runs, saving attendance here should ALSO be rejected.');
        $this->command?->info('');
        $this->command?->info('Verify: log in as that instructor and try to save attendance on the "Other" class, or check instructor_attendance_blocks for instructor_id = '.$instructor->id.'.');
    }

    /**
     * @param  array{label:string,start:Carbon,end:Carbon}  $scenario
     */
    private function makeScenario(User $instructor, array $refs, array $scenario, Carbon $today, Carbon $now, int $phoneCounter): int
    {
        // A class's title always mirrors its course's title in the real create flow
        // (see InstructorClassController::store()), pulled from an existing row in the
        // course catalog via course_id - not a synthetic course made up for this
        // seeder - so the seeded cards read like a real class instead of an internal
        // test label.
        $course = Course::query()->findOrFail($scenario['course_id']);

        $classId = DB::table('study_classes')->insertGetId([
            'title' => $course->title,
            'course_id' => $course->id,
            'lesson_id' => null,
            'teacher_id' => $instructor->id,
            'room_id' => $refs['room_id'],
            'class_type_id' => $refs['class_type_id'],
            'term_id' => $refs['term_id'],
            'time_id' => $scenario['time_id'],
            'status' => 'active',
            'capacity' => 30,
            'start_date' => $today->copy()->subWeeks(3)->toDateString(),
            'end_date' => $today->copy()->addMonths(2)->toDateString(),
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $phone = self::STUDENT_PHONE_PREFIX.str_pad((string) $phoneCounter, 2, '0', STR_PAD_LEFT);
            $phoneCounter++;

            $studentId = DB::table('students')->insertGetId([
                'user_id' => null,
                'full_name' => sprintf('AttBlock %s Student %d', $scenario['label'], $i),
                'gender' => $i % 2 === 0 ? 'female' : 'male',
                'phone' => $phone,
                'student_status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('student_enrollments')->insert([
                'study_class_id' => $classId,
                'student_id' => $studentId,
                'enrollment_status' => 'active',
                'payment_status' => 'paid',
                'fee_amount' => 0,
                'amount_paid' => 0,
                'enrolled_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('class_sessions')->insert([
            'study_class_id' => $classId,
            'instructor_id' => $instructor->id,
            'session_date' => $today->toDateString(),
            'scheduled_start' => $scenario['start']->toDateTimeString(),
            'scheduled_end' => $scenario['end']->toDateTimeString(),
            'status' => ClassSession::STATUS_PENDING,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->command?->info(sprintf(
            '  class #%d  "%s"  ["%s" scenario]  scheduled_start=%s',
            $classId,
            $course->title,
            $scenario['label'],
            $scenario['start']->toDateTimeString(),
        ));

        return $phoneCounter;
    }

    /**
     * @return array{room_id:?int,class_type_id:?int,term_id:?int,time_ids:array{int,int},course_ids:array{int,int}}
     */
    private function resolveReferences(): array
    {
        $term = Term::query()->first()
            ?? Term::query()->create(['term_name' => 'Mon - Fri']);

        // Two distinct slots - see the comment where these are used in run().
        $timeIds = Time::query()->orderBy('id')->limit(2)->pluck('id')->all();

        if (count($timeIds) < 2) {
            $timeIds = [
                $timeIds[0] ?? Time::query()->create(['time_name' => '08:00 am - 09:30 am'])->id,
                Time::query()->create(['time_name' => '11:00 am - 12:30 pm'])->id,
            ];
        }

        // Real rows from the course catalog - see the comment on makeScenario() for
        // why this doesn't create its own course like it used to.
        $courseIds = Course::query()->orderBy('id')->limit(2)->pluck('id')->all();

        if ($courseIds === []) {
            throw new \RuntimeException('No courses found - seed the course catalog before running this seeder.');
        }

        // Only one course in the catalog: both scenarios teach the same one, same as
        // two real sections of one course would.
        if (count($courseIds) < 2) {
            $courseIds[] = $courseIds[0];
        }

        return [
            'room_id' => Room::query()->value('id'),
            'class_type_id' => ClassType::query()->value('class_type_id'),
            'term_id' => $term->id,
            'time_ids' => $timeIds,
            'course_ids' => $courseIds,
        ];
    }

    private function wipePrevious(): void
    {
        $instructorId = DB::table('users')->where('email', self::INSTRUCTOR_EMAIL)->value('id');

        if (! $instructorId) {
            return;
        }

        // Classes now carry a real course's own title (no "[AttBlock Test]" marker to
        // match on), so cleanup is scoped to this seeder's dedicated test instructor
        // instead. study_classes cascades to student_enrollments, class_sessions and
        // student_attendances, so dropping the classes clears the rest.
        $classIds = DB::table('study_classes')->where('teacher_id', $instructorId)->pluck('id');

        if ($classIds->isNotEmpty()) {
            DB::table('study_classes')->whereIn('id', $classIds)->delete();
        }

        DB::table('students')
            ->where('phone', 'like', self::STUDENT_PHONE_PREFIX.'%')
            ->delete();

        DB::table('instructor_attendance_blocks')->where('instructor_id', $instructorId)->delete();
    }
}
