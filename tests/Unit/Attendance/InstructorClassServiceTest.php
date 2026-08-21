<?php

namespace Tests\Unit\Attendance;

use App\Models\ClassSession;
use App\Models\GradingSetting;
use App\Models\StudentAttendance;
use App\Modules\Attendance\Actions\FinalizeAutoRecordedSession;
use App\Modules\Instructor\Services\InstructorClassService;
use Database\Seeders\Core\RoleSeeder;
use Database\Seeders\GradingSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

class InstructorClassServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAttendanceFixtures;

    private InstructorClassService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(GradingSettingSeeder::class);
        Cache::forget(GradingSetting::CACHE_KEY);

        $this->service = app(InstructorClassService::class);
        Carbon::setTestNow(null);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);

        parent::tearDown();
    }

    private function sessionFor(Carbon $start, int $graceMinutes = 20, array $overrides = []): ClassSession
    {
        GradingSetting::where('key', 'attendance.auto_record_grace_minutes')->first()->update([
            'value' => (string) $graceMinutes,
        ]);
        Cache::forget(GradingSetting::CACHE_KEY);

        $class = $overrides['class'] ?? $this->makeStudyClass();

        return ClassSession::create([
            'study_class_id' => $class->id,
            'instructor_id' => $class->teacher_id,
            'session_date' => $start->toDateString(),
            'scheduled_start' => $start,
            'scheduled_end' => $start->copy()->addMinutes(90),
            'status' => ClassSession::STATUS_PENDING,
        ]);
    }

    public function test_manual_attendance_is_rejected_before_the_class_starts(): void
    {
        $now = Carbon::parse('2026-08-21 08:50:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        $class = $this->makeStudyClass();
        $student = $this->makeStudent();
        $enrollment = $this->enroll($class, $student);
        $this->sessionFor(Carbon::parse('2026-08-21 09:00:00', 'Asia/Phnom_Penh'), 20, ['class' => $class]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Attendance can only be tracked from the class start time until the configured grace period ends.');

        $this->service->saveAttendance($class->teacher, $class->id, [
            'attendance_date' => '2026-08-21',
            'records' => [[
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'status' => 'present',
                'note' => null,
            ]],
        ]);
    }

    public function test_manual_attendance_is_rejected_after_the_grace_window(): void
    {
        $now = Carbon::parse('2026-08-21 09:21:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        $class = $this->makeStudyClass();
        $student = $this->makeStudent();
        $enrollment = $this->enroll($class, $student);
        $this->sessionFor(Carbon::parse('2026-08-21 09:00:00', 'Asia/Phnom_Penh'), 20, ['class' => $class]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Attendance can only be tracked from the class start time until the configured grace period ends.');

        $this->service->saveAttendance($class->teacher, $class->id, [
            'attendance_date' => '2026-08-21',
            'records' => [[
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'status' => 'present',
                'note' => null,
            ]],
        ]);
    }

    public function test_manual_attendance_can_be_saved_during_the_class_window(): void
    {
        $now = Carbon::parse('2026-08-21 09:10:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        $class = $this->makeStudyClass();
        $student = $this->makeStudent();
        $enrollment = $this->enroll($class, $student);
        $session = $this->sessionFor(Carbon::parse('2026-08-21 09:00:00', 'Asia/Phnom_Penh'), 20, ['class' => $class]);

        $this->service->saveAttendance($class->teacher, $class->id, [
            'attendance_date' => '2026-08-21',
            'records' => [[
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'status' => 'present',
                'note' => 'On time',
            ]],
        ]);

        $this->assertDatabaseHas('student_attendances', [
            'study_class_id' => $class->id,
            'student_enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'status' => 'present',
            'source' => StudentAttendance::SOURCE_MANUAL,
            'note' => 'On time',
        ]);
        $this->assertSame(ClassSession::STATUS_RECORDED, $session->fresh()->status);
    }

    public function test_auto_recorded_pending_rows_finalize_to_absent_after_override_window(): void
    {
        $now = Carbon::parse('2026-08-21 10:00:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        $class = $this->makeStudyClass();
        $student = $this->makeStudent();
        $enrollment = $this->enroll($class, $student);
        $session = $this->sessionFor(Carbon::parse('2026-08-21 09:00:00', 'Asia/Phnom_Penh'), 20, ['class' => $class]);
        $session->update([
            'status' => ClassSession::STATUS_AUTO_RECORDED,
            'recorded_at' => Carbon::parse('2026-08-20 08:00:00', 'Asia/Phnom_Penh'),
        ]);

        StudentAttendance::create([
            'study_class_id' => $class->id,
            'student_enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'tracked_by' => null,
            'attendance_date' => '2026-08-21',
            'status' => 'pending',
            'source' => StudentAttendance::SOURCE_AUTO,
        ]);

        app(FinalizeAutoRecordedSession::class)->handle($session->id);

        $this->assertDatabaseHas('student_attendances', [
            'study_class_id' => $class->id,
            'student_enrollment_id' => $enrollment->id,
            'status' => 'absent',
            'source' => StudentAttendance::SOURCE_AUTO,
        ]);
    }
}
