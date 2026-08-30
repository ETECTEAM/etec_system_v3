<?php

namespace Database\Seeders\Feature\Attendance;

use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\Course;
use App\Models\Room;
use App\Models\StudentAttendance;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds three of an instructor's classes into the states the pre-attendance
 * ("Attendance Recovery") screen is built around, so the whole flow can be
 * exercised without waiting for real class times / the grace period:
 *
 *   1. "[PreAtt Test] Empty"       today's ClassSession = pre_attendance,
 *                                  every student still unresolved.
 *   2. "[PreAtt Test] Partial"     today's ClassSession = partial, a couple of
 *                                  students already have an attendance row, the
 *                                  rest are unresolved.
 *   3. "[PreAtt Test] Auto-record" today's ClassSession = pending, scheduled
 *                                  well past the grace window, nobody tracked -
 *                                  so `php artisan attendance:auto-record` will
 *                                  flip it to pre_attendance (tests the entry
 *                                  path, not just the recovery screen).
 *
 * All three classes are status=active and owned by the first instructor user,
 * so that instructor can open Track Attendance and complete the recovery.
 *
 * Idempotent: re-running wipes its own data first (classes titled
 * "[PreAtt Test] ...", students with phone 09770000xx). Not part of db:seed.
 *
 * Run:
 *   php artisan db:seed --class="Database\Seeders\Feature\Attendance\PreAttendanceTestSeeder"
 */
class PreAttendanceTestSeeder extends Seeder
{
    private const CLASS_TITLE_PREFIX = '[PreAtt Test]';

    private const STUDENT_PHONE_PREFIX = '0977000';

    private const GRACE_MINUTES = 15;

    public function run(): void
    {
        // whereHas (not the ->role() scope) so a database without the
        // "instructor" role row yet fails soft instead of throwing.
        $instructor = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'instructor'))
            ->orderBy('id')
            ->first();

        if (! $instructor) {
            $this->command?->error('No user with the "instructor" role found - run the base seeders first.');

            return;
        }

        $refs = $this->resolveReferences();

        $this->wipePrevious();

        $today = Carbon::today('Asia/Phnom_Penh');
        $now = Carbon::now('Asia/Phnom_Penh');

        // label, session status, students, already-tracked, scheduled_start
        $scenarios = [
            [
                'label' => 'Empty',
                'session_status' => ClassSession::STATUS_PRE_ATTENDANCE,
                'students' => 5,
                'tracked' => 0,
                'start' => $today->copy()->setTime(9, 0),
                'recorded_at' => $now,
            ],
            [
                'label' => 'Partial',
                'session_status' => ClassSession::STATUS_PARTIAL,
                'students' => 6,
                'tracked' => 2,
                'start' => $today->copy()->setTime(9, 0),
                'recorded_at' => $now,
            ],
            [
                'label' => 'Auto-record',
                'session_status' => ClassSession::STATUS_PENDING,
                'students' => 4,
                'tracked' => 0,
                'start' => $now->copy()->subHours(2),
                'recorded_at' => null,
            ],
        ];

        $phoneCounter = 1;

        foreach ($scenarios as $scenario) {
            $phoneCounter = $this->makeScenario($instructor, $refs, $scenario, $today, $now, $phoneCounter);
        }

        $this->command?->info('');
        $this->command?->info(sprintf('Seeded 3 pre-attendance test classes for instructor "%s" (id %d).', $instructor->name, $instructor->id));
        $this->command?->info('  1. "[PreAtt Test] Empty"       -> shows on Pre Attendance with 5 unresolved');
        $this->command?->info('  2. "[PreAtt Test] Partial"     -> shows on Pre Attendance with 4 unresolved (2 already present)');
        $this->command?->info('  3. "[PreAtt Test] Auto-record" -> run `php artisan attendance:auto-record`, then it appears too');
        $this->command?->info('');
        $this->command?->info('View: log in as that instructor -> sidebar "Pre Attendance" (/dashboard/instructor/pre-attendance).');
        $this->command?->info('Recover: open a class from there, submit the missing students; its session becomes "recorded" and it drops off the list.');
    }

    /**
     * @param  array{label:string,session_status:string,students:int,tracked:int,start:Carbon,recorded_at:?Carbon}  $scenario
     */
    private function makeScenario(User $instructor, array $refs, array $scenario, Carbon $today, Carbon $now, int $phoneCounter): int
    {
        $classId = DB::table('study_classes')->insertGetId([
            'title' => self::CLASS_TITLE_PREFIX.' '.$scenario['label'],
            'course_id' => $refs['course_id'],
            'lesson_id' => null,
            'teacher_id' => $instructor->id,
            'room_id' => $refs['room_id'],
            'class_type_id' => $refs['class_type_id'],
            'term_id' => $refs['term_id'],
            'time_id' => $refs['time_id'],
            'status' => 'active',
            'capacity' => 30,
            'start_date' => $today->copy()->subWeeks(3)->toDateString(),
            'end_date' => $today->copy()->addMonths(2)->toDateString(),
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ]);

        $enrollmentIds = [];
        $studentIds = [];

        for ($i = 1; $i <= $scenario['students']; $i++) {
            $phone = self::STUDENT_PHONE_PREFIX.str_pad((string) $phoneCounter, 2, '0', STR_PAD_LEFT);
            $phoneCounter++;

            $studentId = DB::table('students')->insertGetId([
                'user_id' => null,
                'full_name' => sprintf('PreAtt %s Student %d', $scenario['label'], $i),
                'gender' => $i % 2 === 0 ? 'female' : 'male',
                'phone' => $phone,
                'student_status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $enrollmentId = DB::table('student_enrollments')->insertGetId([
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

            $studentIds[] = $studentId;
            $enrollmentIds[] = $enrollmentId;
        }

        DB::table('class_sessions')->insert([
            'study_class_id' => $classId,
            'instructor_id' => $instructor->id,
            'session_date' => $today->toDateString(),
            'scheduled_start' => $scenario['start']->toDateTimeString(),
            'scheduled_end' => $scenario['start']->copy()->addMinutes(90)->toDateTimeString(),
            'status' => $scenario['session_status'],
            'recorded_at' => $scenario['recorded_at']?->toDateTimeString(),
            'grace_minutes_used' => $scenario['recorded_at'] ? self::GRACE_MINUTES : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        for ($i = 0; $i < $scenario['tracked']; $i++) {
            DB::table('student_attendances')->insert([
                'study_class_id' => $classId,
                'student_enrollment_id' => $enrollmentIds[$i],
                'student_id' => $studentIds[$i],
                'tracked_by' => $instructor->id,
                'attendance_date' => $today->toDateString(),
                'status' => 'present',
                'source' => StudentAttendance::SOURCE_MANUAL,
                'verification_status' => 'verified',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command?->info(sprintf(
            '  class #%d  "%s %s"  session=%s  students=%d  tracked=%d',
            $classId,
            self::CLASS_TITLE_PREFIX,
            $scenario['label'],
            $scenario['session_status'],
            $scenario['students'],
            $scenario['tracked'],
        ));

        return $phoneCounter;
    }

    /**
     * Reuse existing reference rows where possible; fall back to creating the
     * minimum needed so the seeder also runs on a near-empty database.
     *
     * @return array{course_id:int,room_id:?int,class_type_id:?int,term_id:?int,time_id:?int}
     */
    private function resolveReferences(): array
    {
        $course = Course::query()->firstOrCreate(
            ['title' => self::CLASS_TITLE_PREFIX.' Course'],
            ['slug' => 'preatt-test-course', 'status' => 'active'],
        );

        $term = Term::query()->first()
            ?? Term::query()->create(['term_name' => 'Mon - Fri']);

        $time = Time::query()->first()
            ?? Time::query()->create(['time_name' => '08:00 - 09:30']);

        return [
            'course_id' => $course->id,
            'room_id' => Room::query()->value('id'),
            'class_type_id' => ClassType::query()->value('class_type_id'),
            'term_id' => $term->id,
            'time_id' => $time->id,
        ];
    }

    private function wipePrevious(): void
    {
        // study_classes cascades to student_enrollments, class_sessions and
        // student_attendances, so dropping the classes clears the rest.
        $classIds = DB::table('study_classes')
            ->where('title', 'like', self::CLASS_TITLE_PREFIX.'%')
            ->pluck('id');

        if ($classIds->isNotEmpty()) {
            DB::table('study_classes')->whereIn('id', $classIds)->delete();
        }

        DB::table('students')
            ->where('phone', 'like', self::STUDENT_PHONE_PREFIX.'%')
            ->delete();
    }
}
