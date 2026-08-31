<?php

namespace Tests\Unit\Attendance;

use App\Models\ClassSession;
use App\Models\GradingSetting;
use App\Models\StudentAttendance;
use App\Modules\Attendance\Actions\AutoRecordSession;
use Database\Seeders\Core\RoleSeeder;
use Database\Seeders\GradingSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

class AutoRecordSessionTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAttendanceFixtures;

    private AutoRecordSession $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(GradingSettingSeeder::class);
        // The array cache store can outlive a single test's RefreshDatabase
        // transaction, so a stale settings read from a previous test can't leak in.
        Cache::forget(GradingSetting::CACHE_KEY);

        $this->action = app(AutoRecordSession::class);
        Carbon::setTestNow(null);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);

        parent::tearDown();
    }

    private function makeSession(Carbon $now, int $offsetMinutes, array $overrides = []): ClassSession
    {
        $class = $overrides['class'] ?? $this->makeStudyClass();

        return ClassSession::create([
            'study_class_id' => $class->id,
            'instructor_id' => $class->teacher_id,
            'session_date' => $now->toDateString(),
            'scheduled_start' => $now->copy()->addMinutes($offsetMinutes),
            'scheduled_end' => $now->copy()->addMinutes($offsetMinutes + 90),
            'status' => ClassSession::STATUS_PENDING,
        ]);
    }

    // ─── Grace boundary ─────────────────────────────────────────────────────

    public function test_one_minute_before_grace_elapses_nothing_is_recorded(): void
    {
        $now = Carbon::parse('2026-08-18 09:14:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        $class = $this->makeStudyClass();
        $this->enroll($class, $this->makeStudent());
        // Class started at 09:00, grace is 15 minutes -> due at 09:15. 09:14 is one
        // minute early: the command's own query must not even hand this session to
        // the action, so it's simulated here by asserting the action still leaves a
        // session started less than grace-minutes ago alone if called directly is out
        // of scope for the action (which trusts its caller's due-check) - this test
        // instead exercises the full command, which is what actually enforces grace.
        $session = $this->makeSession(Carbon::parse('2026-08-18 09:00:00', 'Asia/Phnom_Penh'), 0, ['class' => $class]);

        Artisan::call('attendance:auto-record');

        $this->assertSame(ClassSession::STATUS_PENDING, $session->fresh()->status);
        $this->assertDatabaseCount('student_attendances', 0);
    }

    public function test_exactly_at_grace_boundary_moves_untracked_session_to_pre_attendance(): void
    {
        $now = Carbon::parse('2026-08-18 09:15:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        $class = $this->makeStudyClass();
        $this->enroll($class, $this->makeStudent());
        $session = $this->makeSession(Carbon::parse('2026-08-18 09:00:00', 'Asia/Phnom_Penh'), 0, ['class' => $class]);

        Artisan::call('attendance:auto-record');

        $this->assertSame(ClassSession::STATUS_PRE_ATTENDANCE, $session->fresh()->status);
        $this->assertDatabaseCount('student_attendances', 0);
    }

    public function test_one_minute_after_grace_elapses_moves_untracked_session_to_pre_attendance(): void
    {
        $now = Carbon::parse('2026-08-18 09:16:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        $class = $this->makeStudyClass();
        $this->enroll($class, $this->makeStudent());
        $session = $this->makeSession(Carbon::parse('2026-08-18 09:00:00', 'Asia/Phnom_Penh'), 0, ['class' => $class]);

        Artisan::call('attendance:auto-record');

        $this->assertSame(ClassSession::STATUS_PRE_ATTENDANCE, $session->fresh()->status);
        $this->assertDatabaseCount('student_attendances', 0);
    }

    // ─── Disabled switch ────────────────────────────────────────────────────

    public function test_disabled_master_switch_records_nothing(): void
    {
        GradingSetting::where('key', 'attendance.auto_record_enabled')->first()->update(['value' => 'false']);

        $now = Carbon::parse('2026-08-18 09:30:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        $class = $this->makeStudyClass();
        $this->enroll($class, $this->makeStudent());
        $session = $this->makeSession(Carbon::parse('2026-08-18 09:00:00', 'Asia/Phnom_Penh'), 0, ['class' => $class]);

        Artisan::call('attendance:auto-record');

        $this->assertSame(ClassSession::STATUS_PENDING, $session->fresh()->status);
        $this->assertDatabaseCount('student_attendances', 0);
    }

    // ─── Race: session already resolved before the action gets to it ──────────

    public function test_session_already_recorded_by_the_instructor_is_left_alone(): void
    {
        $class = $this->makeStudyClass();
        $this->enroll($class, $this->makeStudent());
        $session = $this->makeSession(Carbon::now('Asia/Phnom_Penh'), -30, ['class' => $class]);
        // Simulates InstructorClassService::saveAttendance winning the race and
        // flipping the session before the scheduler's turn.
        $session->update(['status' => ClassSession::STATUS_RECORDED]);

        $this->action->handle($session->id);

        $this->assertDatabaseCount('student_attendances', 0);
        $this->assertSame(ClassSession::STATUS_RECORDED, $session->fresh()->status);
    }

    public function test_partial_tracked_session_preserves_existing_rows_and_marks_partial(): void
    {
        $class = $this->makeStudyClass();
        $student = $this->makeStudent();
        $enrollment = $this->enroll($class, $student);
        $second = $this->enroll($class, $this->makeStudent());

        $session = $this->makeSession(Carbon::now('Asia/Phnom_Penh'), -30, ['class' => $class]);

        // One student's row already exists. The auto-record pass must leave it
        // alone and only create the missing student row.
        StudentAttendance::create([
            'study_class_id' => $class->id,
            'student_enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'attendance_date' => $session->session_date,
            'status' => 'present',
            'source' => StudentAttendance::SOURCE_MANUAL,
        ]);

        $this->action->handle($session->id);

        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertDatabaseHas('student_attendances', [
            'student_enrollment_id' => $enrollment->id,
            'source' => StudentAttendance::SOURCE_MANUAL,
        ]);
        $this->assertDatabaseMissing('student_attendances', [
            'student_enrollment_id' => $second->id,
        ]);
        $this->assertSame(ClassSession::STATUS_PARTIAL, $session->fresh()->status);
    }

    public function test_fully_tracked_session_is_auto_recorded_without_overwriting_statuses(): void
    {
        $class = $this->makeStudyClass();
        $present = $this->makeStudent();
        $late = $this->makeStudent();
        $presentEnrollment = $this->enroll($class, $present);
        $lateEnrollment = $this->enroll($class, $late);
        $session = $this->makeSession(Carbon::now('Asia/Phnom_Penh'), -30, ['class' => $class]);

        StudentAttendance::create([
            'study_class_id' => $class->id,
            'student_enrollment_id' => $presentEnrollment->id,
            'student_id' => $present->id,
            'attendance_date' => $session->session_date,
            'status' => 'present',
            'source' => StudentAttendance::SOURCE_QR,
        ]);

        StudentAttendance::create([
            'study_class_id' => $class->id,
            'student_enrollment_id' => $lateEnrollment->id,
            'student_id' => $late->id,
            'attendance_date' => $session->session_date,
            'status' => 'permission',
            'source' => StudentAttendance::SOURCE_MANUAL,
        ]);

        $this->action->handle($session->id);

        $this->assertSame(ClassSession::STATUS_AUTO_RECORDED, $session->fresh()->status);
        $this->assertDatabaseCount('student_attendances', 2);
        $this->assertDatabaseHas('student_attendances', [
            'student_enrollment_id' => $presentEnrollment->id,
            'status' => 'present',
            'source' => StudentAttendance::SOURCE_QR,
        ]);
        $this->assertDatabaseHas('student_attendances', [
            'student_enrollment_id' => $lateEnrollment->id,
            'status' => 'permission',
            'source' => StudentAttendance::SOURCE_MANUAL,
        ]);
    }

    public function test_auto_record_is_idempotent_for_partial_sessions(): void
    {
        $class = $this->makeStudyClass();
        $student = $this->makeStudent();
        $enrollment = $this->enroll($class, $student);
        $this->enroll($class, $this->makeStudent());
        $session = $this->makeSession(Carbon::now('Asia/Phnom_Penh'), -30, ['class' => $class]);

        StudentAttendance::create([
            'study_class_id' => $class->id,
            'student_enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'attendance_date' => $session->session_date,
            'status' => 'present',
            'source' => StudentAttendance::SOURCE_QR,
        ]);

        $this->action->handle($session->id);
        $this->action->handle($session->id);

        $this->assertSame(ClassSession::STATUS_PARTIAL, $session->fresh()->status);
        $this->assertDatabaseCount('student_attendances', 1);
    }

    // ─── Past-end-time session ──────────────────────────────────────────────

    public function test_session_past_its_own_end_time_is_moved_to_pre_attendance_not_recorded(): void
    {
        $now = Carbon::parse('2026-08-18 11:00:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        $class = $this->makeStudyClass();
        $this->enroll($class, $this->makeStudent());
        // 09:00-10:30 class; now is 11:00, well past scheduled_end.
        $session = $this->makeSession(Carbon::parse('2026-08-18 09:00:00', 'Asia/Phnom_Penh'), 0, ['class' => $class]);

        $this->action->handle($session->id);

        $this->assertSame(ClassSession::STATUS_PRE_ATTENDANCE, $session->fresh()->status);
        $this->assertDatabaseCount('student_attendances', 0);
    }

    // ─── Zero students ──────────────────────────────────────────────────────

    public function test_session_with_no_active_students_is_skipped(): void
    {
        $class = $this->makeStudyClass();
        // No enrollment.
        $session = $this->makeSession(Carbon::now('Asia/Phnom_Penh'), -30, ['class' => $class]);

        $this->action->handle($session->id);

        $this->assertSame(ClassSession::STATUS_SKIPPED, $session->fresh()->status);
    }
}
