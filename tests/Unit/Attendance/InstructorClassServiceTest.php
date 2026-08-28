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

    public function test_manual_attendance_can_be_saved_before_start_when_track_anytime_is_enabled(): void
    {
        GradingSetting::query()->updateOrCreate(
            ['key' => 'attendance.auto_record_allow_track_anytime'],
            ['value' => 'true', 'type' => 'boolean', 'label' => 'Allow tracking anytime', 'group' => 'attendance'],
        );
        Cache::forget(GradingSetting::CACHE_KEY);

        $now = Carbon::parse('2026-08-21 08:50:00', 'Asia/Phnom_Penh');
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
                'note' => null,
            ]],
        ]);

        $this->assertSame(ClassSession::STATUS_RECORDED, $session->fresh()->status);
        $this->assertDatabaseHas('student_attendances', [
            'study_class_id' => $class->id,
            'student_enrollment_id' => $enrollment->id,
            'status' => 'present',
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

    public function test_manual_save_before_grace_finalizes_all_submitted_students_without_pre_attendance(): void
    {
        $now = Carbon::parse('2026-08-21 09:10:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        $class = $this->makeStudyClass();
        $present = $this->makeStudent();
        $untouched = $this->makeStudent();
        $presentEnrollment = $this->enroll($class, $present);
        $untouchedEnrollment = $this->enroll($class, $untouched);
        $session = $this->sessionFor(Carbon::parse('2026-08-21 09:00:00', 'Asia/Phnom_Penh'), 20, ['class' => $class]);

        $this->service->saveAttendance($class->teacher, $class->id, [
            'attendance_date' => '2026-08-21',
            'records' => [
                [
                    'student_id' => $present->id,
                    'enrollment_id' => $presentEnrollment->id,
                    'status' => 'present',
                    'note' => null,
                ],
                [
                    'student_id' => $untouched->id,
                    'enrollment_id' => $untouchedEnrollment->id,
                    'status' => 'absent',
                    'note' => null,
                ],
            ],
        ]);

        $this->assertSame(ClassSession::STATUS_RECORDED, $session->fresh()->status);
        $this->assertDatabaseCount('student_attendances', 2);
    }

    public function test_auto_recorded_session_cannot_be_manually_saved_even_when_track_anytime_is_enabled(): void
    {
        GradingSetting::query()->updateOrCreate(
            ['key' => 'attendance.auto_record_allow_track_anytime'],
            ['value' => 'true', 'type' => 'boolean', 'label' => 'Allow tracking anytime', 'group' => 'attendance'],
        );
        Cache::forget(GradingSetting::CACHE_KEY);

        $now = Carbon::parse('2026-08-21 09:30:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        $class = $this->makeStudyClass();
        $student = $this->makeStudent();
        $enrollment = $this->enroll($class, $student);
        $session = $this->sessionFor(Carbon::parse('2026-08-21 09:00:00', 'Asia/Phnom_Penh'), 20, ['class' => $class]);
        $session->update([
            'status' => ClassSession::STATUS_AUTO_RECORDED,
            'recorded_at' => Carbon::parse('2026-08-21 09:20:00', 'Asia/Phnom_Penh'),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The system already auto-recorded this class.');

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

    public function test_partial_pre_attendance_completion_preserves_existing_rows_and_records_remaining_students(): void
    {
        $now = Carbon::parse('2026-08-21 09:30:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        $class = $this->makeStudyClass();
        $tracked = $this->makeStudent();
        $unresolved = $this->makeStudent();
        $trackedEnrollment = $this->enroll($class, $tracked);
        $unresolvedEnrollment = $this->enroll($class, $unresolved);
        $session = $this->sessionFor(Carbon::parse('2026-08-21 09:00:00', 'Asia/Phnom_Penh'), 20, ['class' => $class]);
        $session->update([
            'status' => ClassSession::STATUS_PARTIAL,
            'recorded_at' => Carbon::parse('2026-08-21 09:20:00', 'Asia/Phnom_Penh'),
            'grace_minutes_used' => 20,
        ]);

        StudentAttendance::create([
            'study_class_id' => $class->id,
            'student_enrollment_id' => $trackedEnrollment->id,
            'student_id' => $tracked->id,
            'attendance_date' => '2026-08-21',
            'status' => 'present',
            'source' => StudentAttendance::SOURCE_QR,
        ]);

        $this->service->saveAttendance($class->teacher, $class->id, [
            'attendance_date' => '2026-08-21',
            'records' => [
                [
                    'student_id' => $tracked->id,
                    'enrollment_id' => $trackedEnrollment->id,
                    'status' => 'present',
                    'note' => null,
                ],
                [
                    'student_id' => $unresolved->id,
                    'enrollment_id' => $unresolvedEnrollment->id,
                    'status' => 'absent',
                    'note' => null,
                ],
            ],
        ]);

        $this->assertSame(ClassSession::STATUS_RECORDED, $session->fresh()->status);
        $this->assertDatabaseCount('student_attendances', 2);
        $this->assertDatabaseHas('student_attendances', [
            'student_enrollment_id' => $trackedEnrollment->id,
            'status' => 'present',
            'source' => StudentAttendance::SOURCE_QR,
        ]);
        $this->assertDatabaseHas('student_attendances', [
            'student_enrollment_id' => $unresolvedEnrollment->id,
            'status' => 'absent',
            'source' => StudentAttendance::SOURCE_MANUAL,
        ]);
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
