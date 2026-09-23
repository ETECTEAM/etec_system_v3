<?php

namespace Tests\Unit\Attendance;

use App\Models\AttendanceRuleSetting;
use App\Models\ClassSession;
use App\Models\InstructorAttendanceBlock;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentAttendanceBlock;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\User;
use App\Modules\AbsenceBlock\Actions\AutoBlockStudent;
use App\Modules\AbsenceBlock\Services\AbsenceBlockEvaluator;
use App\Modules\AbsenceBlock\Services\PermissionLimitEvaluator;
use App\Modules\AbsenceBlock\Support\LockState;
use App\Modules\Attendance\Actions\AutoFillTriggeringSessionFromLastWeek;
use App\Modules\Attendance\Actions\BackfillAllMissedSessionsFromLastWeek;
use App\Modules\Attendance\Actions\ApproveInstructorAttendanceBlock;
use App\Modules\Instructor\Services\InstructorClassService;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

/**
 * The two admin approve paths on an instructor attendance block — plain Approve
 * (fills only the triggering session) and Approve After Permission (fills EVERY
 * stuck session across the instructor's classes) — plus the instructor's reason
 * claim and the bulk sweep's query budget.
 * See docs/instructor-attendance-block-approve-after-permission.md.
 */
class InstructorAttendanceBlockApproveAfterPermissionTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAttendanceFixtures;

    private const SESSION_DATE = '2026-09-15';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Cache::forget(AttendanceRuleSetting::CACHE_KEY);
        Carbon::setTestNow('2026-09-20 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function makeSession(StudyClass $class, string $date, string $status): ClassSession
    {
        return ClassSession::create([
            'study_class_id' => $class->id,
            'instructor_id' => $class->teacher_id,
            'session_date' => $date,
            'scheduled_start' => $date.' 09:00:00',
            'scheduled_end' => $date.' 10:30:00',
            'status' => $status,
        ]);
    }

    private function makeBlock(User $instructor, ClassSession $trigger, string $blockedAt = '2026-09-15 10:00:00'): InstructorAttendanceBlock
    {
        return InstructorAttendanceBlock::create([
            'instructor_id' => $instructor->id,
            'triggered_by_session_id' => $trigger->id,
            'reason' => 'Attendance was not recorded.',
            'status' => InstructorAttendanceBlock::STATUS_PENDING_REVIEW,
            'blocked_at' => $blockedAt,
        ]);
    }

    private function phone(string $seed): string
    {
        return '092'.substr(md5($seed), 0, 8);
    }

    /** Enrolls, then records the student's last-week row for $sessionDate. */
    private function lastWeek(StudyClass $class, Student $student, string $sessionDate, string $status): StudentEnrollment
    {
        $enrollment = $this->enroll($class, $student);
        StudentAttendance::create([
            'study_class_id' => $class->id,
            'student_enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'attendance_date' => Carbon::parse($sessionDate)->subDays(7)->toDateString(),
            ...StudentAttendance::flagsFor($status),
            'source' => StudentAttendance::SOURCE_MANUAL,
        ]);

        return $enrollment;
    }

    /**
     * A class with one stuck session and a five-student roster covering every
     * outcome branch: last-week present, last-week absent, permission-over-quota,
     * open absence lock, and no prior record. Distinct phone seeds per scenario so
     * the tel+course counters can't leak across phases.
     *
     * @return array{StudyClass, ClassSession}
     */
    private function buildScenario(string $tag, User $instructor): array
    {
        $course = $this->makeCourse();
        $class = $this->makeStudyClass(['teacher' => $instructor, 'course' => $course]);
        $session = $this->makeSession($class, self::SESSION_DATE, ClassSession::STATUS_PRE_ATTENDANCE);

        $this->lastWeek($class, $this->makeStudentWithPhone($this->phone("$tag.present")), self::SESSION_DATE, StudentAttendance::STATUS_PRESENT);

        $this->lastWeek($class, $this->makeStudentWithPhone($this->phone("$tag.absent")), self::SESSION_DATE, StudentAttendance::STATUS_ABSENT);

        $quota = $this->makeStudentWithPhone($this->phone("$tag.quota"));
        $quotaEnrollment = $this->lastWeek($class, $quota, self::SESSION_DATE, StudentAttendance::STATUS_PERMISSION);
        // A prior permission earlier this ISO week burns the single weekly slot, so
        // the copied permission must be pushed over quota and stored as an absence.
        StudentAttendance::create([
            'study_class_id' => $class->id,
            'student_enrollment_id' => $quotaEnrollment->id,
            'student_id' => $quota->id,
            'attendance_date' => Carbon::parse(self::SESSION_DATE)->subDay()->toDateString(),
            ...StudentAttendance::flagsFor(StudentAttendance::STATUS_PERMISSION),
            'source' => StudentAttendance::SOURCE_MANUAL,
        ]);

        $locked = $this->makeStudentWithPhone($this->phone("$tag.locked"));
        $this->lastWeek($class, $locked, self::SESSION_DATE, StudentAttendance::STATUS_PRESENT);
        StudentAttendanceBlock::create([
            'student_id' => $locked->id,
            'student_tel' => $locked->phone,
            'course_id' => $course->id,
            'study_class_id' => $class->id,
            'block_type' => StudentAttendanceBlock::TYPE_ABSENCE,
            'is_approved' => false,
            'blocked_at' => now(),
            'cycle_start_date' => '2026-04-01',
        ]);

        $this->enroll($class, $this->makeStudentWithPhone($this->phone("$tag.fresh")));

        return [$class, $session];
    }

    private function makeStudentWithPhone(string $phone): Student
    {
        return Student::query()->create([
            'full_name' => 'Student '.$phone,
            'gender' => 'male',
            'phone' => $phone,
        ]);
    }

    private function attendanceSnapshot(int $classId, string $date): array
    {
        return DB::table('student_attendances')
            ->where('study_class_id', $classId)
            ->whereDate('attendance_date', $date)
            ->orderBy('id')
            ->get(['present', 'absent', 'permission', 'late', 'locked', 'lock_reason', 'source', 'note'])
            ->toArray();
    }

    // ─── Plain Approve ──────────────────────────────────────────────────────

    public function test_plain_approve_backfills_only_the_triggering_session(): void
    {
        $instructor = $this->makeInstructor();
        $actor = User::factory()->create();

        $classA = $this->makeStudyClass(['teacher' => $instructor]);
        $sessionA = $this->makeSession($classA, '2026-09-10', ClassSession::STATUS_PRE_ATTENDANCE);
        $this->lastWeek($classA, $this->makeStudentWithPhone($this->phone('a1')), '2026-09-10', StudentAttendance::STATUS_PRESENT);
        $this->lastWeek($classA, $this->makeStudentWithPhone($this->phone('a2')), '2026-09-10', StudentAttendance::STATUS_PRESENT);

        // Same instructor, same block window, a second stuck session that plain
        // Approve must NOT touch.
        $classB = $this->makeStudyClass(['teacher' => $instructor]);
        $sessionB = $this->makeSession($classB, '2026-09-12', ClassSession::STATUS_PRE_ATTENDANCE);
        $this->lastWeek($classB, $this->makeStudentWithPhone($this->phone('b1')), '2026-09-12', StudentAttendance::STATUS_PRESENT);

        $block = $this->makeBlock($instructor, $sessionA);

        app(ApproveInstructorAttendanceBlock::class)->handle($block, $actor);

        $block->refresh();
        $this->assertSame(InstructorAttendanceBlock::STATUS_APPROVED_UNBLOCK, $block->status);
        $this->assertSame(InstructorAttendanceBlock::REASON_GENERAL, $block->reviewed_reason_type);
        $this->assertSame($actor->id, $block->reviewed_by);
        $this->assertNotNull($block->reviewed_at);

        $this->assertSame(ClassSession::STATUS_AUTO_RECORDED, $sessionA->fresh()->status);
        $this->assertSame(ClassSession::STATUS_PRE_ATTENDANCE, $sessionB->fresh()->status);

        $this->assertDatabaseCount('student_attendances', 2);
        $this->assertDatabaseMissing('student_attendances', [
            'study_class_id' => $classB->id,
            'attendance_date' => '2026-09-12',
        ]);
    }

    // ─── Approve After Permission ───────────────────────────────────────────

    public function test_approve_after_permission_backfills_every_stuck_session_before_block_date(): void
    {
        $instructor = $this->makeInstructor();
        $other = $this->makeInstructor();
        $actor = User::factory()->create();

        $classA = $this->makeStudyClass(['teacher' => $instructor]);
        $sessionA = $this->makeSession($classA, '2026-09-10', ClassSession::STATUS_PRE_ATTENDANCE);
        $this->lastWeek($classA, $this->makeStudentWithPhone($this->phone('sweep-a')), '2026-09-10', StudentAttendance::STATUS_PRESENT);

        // "partial" session, one student already tracked -> that row is preserved,
        // the missing student is filled in.
        $classB = $this->makeStudyClass(['teacher' => $instructor]);
        $sessionB = $this->makeSession($classB, '2026-09-12', ClassSession::STATUS_PARTIAL);
        $existing = $this->makeStudentWithPhone($this->phone('sweep-b0'));
        $existingEnrollment = $this->enroll($classB, $existing);
        StudentAttendance::create([
            'study_class_id' => $classB->id,
            'student_enrollment_id' => $existingEnrollment->id,
            'student_id' => $existing->id,
            'attendance_date' => '2026-09-12',
            ...StudentAttendance::flagsFor(StudentAttendance::STATUS_PRESENT),
            'source' => StudentAttendance::SOURCE_MANUAL,
        ]);
        $this->lastWeek($classB, $this->makeStudentWithPhone($this->phone('sweep-b1')), '2026-09-12', StudentAttendance::STATUS_PRESENT);

        // Session AFTER blocked_at: must stay stuck.
        $classC = $this->makeStudyClass(['teacher' => $instructor]);
        $sessionC = $this->makeSession($classC, '2026-09-16', ClassSession::STATUS_PRE_ATTENDANCE);
        $this->lastWeek($classC, $this->makeStudentWithPhone($this->phone('sweep-c')), '2026-09-16', StudentAttendance::STATUS_PRESENT);

        // Already finalised: must stay as-is.
        $classD = $this->makeStudyClass(['teacher' => $instructor]);
        $sessionD = $this->makeSession($classD, '2026-09-11', ClassSession::STATUS_RECORDED);

        // A stuck session belonging to a different instructor: must stay untouched.
        $classE = $this->makeStudyClass(['teacher' => $other]);
        $sessionE = $this->makeSession($classE, '2026-09-13', ClassSession::STATUS_PRE_ATTENDANCE);
        $this->lastWeek($classE, $this->makeStudentWithPhone($this->phone('sweep-e')), '2026-09-13', StudentAttendance::STATUS_PRESENT);

        $block = $this->makeBlock($instructor, $sessionA);

        app(ApproveInstructorAttendanceBlock::class)->handle($block, $actor, InstructorAttendanceBlock::REASON_PERMISSION);

        $block->refresh();
        $this->assertSame(InstructorAttendanceBlock::STATUS_APPROVED_UNBLOCK, $block->status);
        $this->assertSame(InstructorAttendanceBlock::REASON_PERMISSION, $block->reviewed_reason_type);
        $this->assertSame($actor->id, $block->reviewed_by);

        $this->assertSame(ClassSession::STATUS_AUTO_RECORDED, $sessionA->fresh()->status);
        $this->assertSame(ClassSession::STATUS_AUTO_RECORDED, $sessionB->fresh()->status);
        $this->assertSame(ClassSession::STATUS_PRE_ATTENDANCE, $sessionC->fresh()->status);
        $this->assertSame(ClassSession::STATUS_RECORDED, $sessionD->fresh()->status);
        $this->assertSame(ClassSession::STATUS_PRE_ATTENDANCE, $sessionE->fresh()->status);

        $this->assertDatabaseCount('student_attendances', 4);
        $this->assertDatabaseMissing('student_attendances', ['study_class_id' => $classC->id]);
        $this->assertDatabaseMissing('student_attendances', ['study_class_id' => $classD->id]);
        $this->assertDatabaseMissing('student_attendances', ['study_class_id' => $classE->id]);
        $this->assertDatabaseHas('student_attendances', [
            'study_class_id' => $classB->id,
            'student_enrollment_id' => $existingEnrollment->id,
            'source' => StudentAttendance::SOURCE_MANUAL,
        ]);
    }

    public function test_approve_after_permission_is_a_noop_when_nothing_is_stuck(): void
    {
        $instructor = $this->makeInstructor();
        $actor = User::factory()->create();

        $class = $this->makeStudyClass(['teacher' => $instructor]);
        $session = $this->makeSession($class, '2026-09-16', ClassSession::STATUS_RECORDED);
        $block = $this->makeBlock($instructor, $session, '2026-09-15 10:00:00');

        app(ApproveInstructorAttendanceBlock::class)->handle($block, $actor, InstructorAttendanceBlock::REASON_PERMISSION);

        $block->refresh();
        $this->assertSame(InstructorAttendanceBlock::STATUS_APPROVED_UNBLOCK, $block->status);
        $this->assertSame(InstructorAttendanceBlock::REASON_PERMISSION, $block->reviewed_reason_type);
        $this->assertSame(ClassSession::STATUS_RECORDED, $session->fresh()->status);
        $this->assertDatabaseCount('student_attendances', 0);
    }

    public function test_approved_block_and_invalid_reason_are_rejected(): void
    {
        $instructor = $this->makeInstructor();
        $actor = User::factory()->create();
        $class = $this->makeStudyClass(['teacher' => $instructor]);
        $session = $this->makeSession($class, '2026-09-10', ClassSession::STATUS_PRE_ATTENDANCE);
        $block = $this->makeBlock($instructor, $session);
        $block->update(['status' => InstructorAttendanceBlock::STATUS_APPROVED_UNBLOCK]);

        $this->expectException(ValidationException::class);
        app(ApproveInstructorAttendanceBlock::class)->handle($block, $actor, InstructorAttendanceBlock::REASON_GENERAL);

        $fresh = $this->makeBlock($this->makeInstructor(), $this->makeSession($this->makeStudyClass(), '2026-09-10', ClassSession::STATUS_PRE_ATTENDANCE));

        try {
            app(ApproveInstructorAttendanceBlock::class)->handle($fresh, $actor, 'not-a-reason');
            $this->fail('An invalid reviewed reason must be rejected.');
        } catch (ValidationException) {
            $this->assertSame(InstructorAttendanceBlock::STATUS_PENDING_REVIEW, $fresh->fresh()->status);
        }
    }

    // ─── Bulk path == single-session path ───────────────────────────────────

    public function test_bulk_backfill_produces_the_same_rows_as_the_single_session_path(): void
    {
        $instructor = $this->makeInstructor();
        $actor = User::factory()->create();

        [$classOne, $sessionOne] = $this->buildScenario('one', $instructor);
        app(AutoFillTriggeringSessionFromLastWeek::class)->handle($sessionOne->id, $actor);

        [$classTwo, $sessionTwo] = $this->buildScenario('two', $instructor);
        $block = $this->makeBlock($instructor, $sessionTwo);
        app(BackfillAllMissedSessionsFromLastWeek::class)->handle($block, $actor);

        $this->assertSame(ClassSession::STATUS_AUTO_RECORDED, $sessionOne->fresh()->status);
        $this->assertSame(ClassSession::STATUS_AUTO_RECORDED, $sessionTwo->fresh()->status);
        $this->assertCount(5, $this->attendanceSnapshot($classOne->id, self::SESSION_DATE));
        $this->assertSame(
            $this->attendanceSnapshot($classOne->id, self::SESSION_DATE),
            $this->attendanceSnapshot($classTwo->id, self::SESSION_DATE),
        );

        // Edge cases exercised by both phases: the over-quota permission came out
        // as an absence with the conversion note, and the locked student stayed
        // locked with the soft-lock reason attached.
        $rowsTwo = DB::table('student_attendances')
            ->where('study_class_id', $classTwo->id)
            ->whereDate('attendance_date', self::SESSION_DATE)
            ->orderBy('id')
            ->get();
        $permissionConverted = $rowsTwo->firstWhere('note', PermissionLimitEvaluator::NOTE_CONVERTED);
        $this->assertNotNull($permissionConverted);
        $this->assertSame(1, $permissionConverted->absent);
        $this->assertSame(0, $permissionConverted->permission);

        $lockedRow = $rowsTwo->firstWhere('locked', 1);
        $this->assertNotNull($lockedRow);
        $this->assertSame(1, $lockedRow->absent);
        $this->assertSame(StudentAttendanceBlock::REASON_SOFT, $lockedRow->lock_reason);
        $this->assertNotNull($lockedRow->locked_block_id);
        $this->assertSame(StudentAttendance::SOURCE_AUTO, $lockedRow->source);

        $this->assertSame((int) $lockedRow->locked_block_id, StudentAttendanceBlock::query()->firstOrFail()->id);
    }

    // ─── Query budget ───────────────────────────────────────────────────────

    public function test_bulk_sweep_stays_within_a_flat_query_budget(): void
    {
        $instructor = $this->makeInstructor();
        $actor = User::factory()->create();
        $course = $this->makeCourse();

        $sessions = collect();

        foreach (['2026-09-11', '2026-09-15'] as $date) {
            foreach (range(1, 3) as $i) {
                $class = $this->makeStudyClass(['teacher' => $instructor, 'course' => $course]);
                $session = $this->makeSession($class, $date, ClassSession::STATUS_PRE_ATTENDANCE);
                foreach (range(1, 6) as $s) {
                    $this->lastWeek($class, $this->makeStudentWithPhone($this->phone("budget.$date.$i.$s")), $date, StudentAttendance::STATUS_ABSENT);
                }
                $sessions->push($session);
            }
        }

        // All last-week rows are absent, so no permissions are written and the
        // lock evaluator is the only per-student system consulted. Mock it (and
        // the block raiser) out to count the sweep's own queries only.
        $this->mock(AbsenceBlockEvaluator::class, fn ($mock) => $mock->shouldReceive('evaluate')->andReturn(LockState::unlocked()));
        $this->mock(AutoBlockStudent::class, fn ($mock) => $mock->shouldReceive('handle')->andReturnNull());

        $block = $this->makeBlock($instructor, $sessions->first(), '2026-09-15 10:00:00');

        $count = 0;
        DB::listen(function () use (&$count): void {
            $count++;
        });

        app(BackfillAllMissedSessionsFromLastWeek::class)->handle($block, $actor);

        // 6 sessions x 6 students all filled. A naive per-row/per-session loop
        // would issue dozens of extra queries; the swept bulk path must stay far
        // under 80 for the whole pass.
        $this->assertSame(36, DB::table('student_attendances')->whereIn('study_class_id', $sessions->pluck('study_class_id'))->count());
        $this->assertLessThan(80, $count);
    }

    // ─── Instructor's stated reason ─────────────────────────────────────────

    public function test_instructor_request_stores_and_refreshes_the_stated_reason(): void
    {
        $instructor = $this->makeInstructor();
        $class = $this->makeStudyClass(['teacher' => $instructor]);
        $session = $this->makeSession($class, '2026-09-10', ClassSession::STATUS_PRE_ATTENDANCE);
        $block = $this->makeBlock($instructor, $session);
        $block->update(['status' => InstructorAttendanceBlock::STATUS_ACTIVE]);

        $service = app(InstructorClassService::class);

        $service->requestAttendanceUnblock($instructor, InstructorAttendanceBlock::REASON_PERMISSION);
        $block->refresh();
        $this->assertSame(InstructorAttendanceBlock::STATUS_PENDING_REVIEW, $block->status);
        $this->assertSame(InstructorAttendanceBlock::REASON_PERMISSION, $block->unblock_reason_type);
        $this->assertNotNull($block->unblock_requested_at);
        $this->assertNull($block->reviewed_reason_type);

        // A resubmit with a corrected claim refreshes the reason, not the review state.
        $service->requestAttendanceUnblock($instructor, InstructorAttendanceBlock::REASON_GENERAL);
        $this->assertSame(InstructorAttendanceBlock::REASON_GENERAL, $block->fresh()->unblock_reason_type);

        $this->expectException(ValidationException::class);
        $service->requestAttendanceUnblock($instructor, 'bogus');
    }
}