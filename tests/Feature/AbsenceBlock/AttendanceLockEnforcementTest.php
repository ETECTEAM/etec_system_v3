<?php

namespace Tests\Feature\AbsenceBlock;

use App\Models\GradingSetting;
use App\Models\StudentAttendance;
use App\Models\StudentAttendanceBlock;
use App\Modules\Instructor\Services\InstructorClassService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\AbsenceBlock\Concerns\BuildsAbsenceScenario;
use Tests\TestCase;

/**
 * The "instructor CANNOT mark them present" half of the spec, exercised through
 * the real InstructorClassService::saveAttendance path.
 */
class AttendanceLockEnforcementTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAbsenceScenario;

    private InstructorClassService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\Core\RoleSeeder::class, \Database\Seeders\GradingSettingSeeder::class]);

        GradingSetting::query()->updateOrCreate(
            ['key' => 'attendance.auto_record_allow_track_anytime'],
            ['value' => 'true', 'type' => 'boolean', 'label' => 'x', 'group' => 'attendance'],
        );
        Cache::forget(GradingSetting::CACHE_KEY);

        $this->service = app(InstructorClassService::class);
        Carbon::setTestNow('2026-05-20 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    private function save(int $classId, $teacher, int $studentId, int $enrollmentId, string $status, string $date): void
    {
        $this->service->saveAttendance($teacher, $classId, [
            'attendance_date' => $date,
            'records' => [[
                'student_id' => $studentId,
                'enrollment_id' => $enrollmentId,
                'status' => $status,
                'note' => null,
            ]],
        ]);
    }

    public function test_a_locked_student_cannot_be_marked_present(): void
    {
        $this->activeAbsenceRule(['limit_count' => 3]);
        $course = $this->makeCourseNamed('C');
        $class = $this->weekdayClass($course);
        $student = $this->studentWithPhone('011abc123');
        $enrollment = $this->enroll($class, $student);

        StudentAttendanceBlock::create([
            'student_id' => $student->id,
            'student_tel' => $student->phone,
            'course_id' => $course->id,
            'block_type' => StudentAttendanceBlock::TYPE_ABSENCE,
            'is_approved' => false,
            'blocked_at' => now(),
            'cycle_start_date' => '2026-04-01',
        ]);

        $this->save($class->id, $class->teacher, $student->id, $enrollment->id, 'present', '2026-05-20');

        $row = StudentAttendance::firstOrFail();
        $this->assertSame('absent', $row->status);
        $this->assertTrue($row->locked);
        $this->assertSame(StudentAttendanceBlock::REASON_SOFT, $row->lock_reason);
    }

    public function test_reaching_the_threshold_through_the_save_path_raises_a_block(): void
    {
        $this->activeAbsenceRule(['limit_count' => 3]);
        $class = $this->weekdayClass();
        $student = $this->studentWithPhone('011abc456');
        $enrollment = $this->enroll($class, $student);

        foreach (['2026-05-06', '2026-05-13', '2026-05-20'] as $d) {
            $this->save($class->id, $class->teacher, $student->id, $enrollment->id, 'absent', $d);
        }

        $this->assertSame(1, StudentAttendanceBlock::query()->ofType('absence')->open()->count());
        $this->assertSame(3, StudentAttendance::where('locked', true)->count());
    }

    public function test_an_over_quota_permission_is_recorded_as_absence(): void
    {
        $class = $this->weekdayClass();
        $student = $this->studentWithPhone('011abc789');
        $enrollment = $this->enroll($class, $student);

        $this->save($class->id, $class->teacher, $student->id, $enrollment->id, 'permission', '2026-05-18');
        $this->save($class->id, $class->teacher, $student->id, $enrollment->id, 'permission', '2026-05-20');

        $rows = StudentAttendance::orderBy('attendance_date')->get();
        $this->assertSame('permission', $rows[0]->status);
        $this->assertSame('absent', $rows[1]->status);
        $this->assertSame('Permission limit exceeded — counted as absence', $rows[1]->note);
    }
}
