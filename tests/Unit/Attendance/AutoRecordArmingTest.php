<?php

namespace Tests\Unit\Attendance;

use App\Models\ClassSession;
use App\Models\GradingSetting;
use App\Models\InstructorAttendanceBlock;
use App\Modules\Instructor\Services\InstructorClassService;
use Database\Seeders\Core\RoleSeeder;
use Database\Seeders\GradingSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

class AutoRecordArmingTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAttendanceFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(GradingSettingSeeder::class);
        Cache::forget(GradingSetting::CACHE_KEY);
        Carbon::setTestNow(null);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);

        parent::tearDown();
    }

    private function pendingSessionFor(array $classOverrides): ClassSession
    {
        $class = $this->makeStudyClass($classOverrides);
        $this->enroll($class, $this->makeStudent());
        $start = Carbon::parse('2026-08-21 09:00:00', 'Asia/Phnom_Penh');

        return ClassSession::create([
            'study_class_id' => $class->id,
            'instructor_id' => $class->teacher_id,
            'session_date' => $start->toDateString(),
            'scheduled_start' => $start,
            'scheduled_end' => $start->copy()->addMinutes(90),
            'status' => ClassSession::STATUS_PENDING,
        ]);
    }

    public function test_a_class_never_tracked_is_left_alone_and_its_instructor_is_not_blocked(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-21 09:25:00', 'Asia/Phnom_Penh'));
        $session = $this->pendingSessionFor(['autoRecordStartedOn' => null]);

        Artisan::call('attendance:auto-record');

        $this->assertSame(ClassSession::STATUS_PENDING, $session->fresh()->status);
        $this->assertSame(0, InstructorAttendanceBlock::query()->count());
    }

    public function test_the_first_tracked_day_itself_is_not_auto_recorded(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-21 09:25:00', 'Asia/Phnom_Penh'));
        $session = $this->pendingSessionFor(['autoRecordStartedOn' => '2026-08-21']);

        Artisan::call('attendance:auto-record');

        $this->assertSame(ClassSession::STATUS_PENDING, $session->fresh()->status);
    }

    public function test_the_day_after_the_first_tracked_day_is_auto_recorded(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-21 09:25:00', 'Asia/Phnom_Penh'));
        $session = $this->pendingSessionFor(['autoRecordStartedOn' => '2026-08-20']);

        Artisan::call('attendance:auto-record');

        $this->assertSame(ClassSession::STATUS_PRE_ATTENDANCE, $session->fresh()->status);
    }

    public function test_the_instructors_first_save_arms_the_class(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-21 09:05:00', 'Asia/Phnom_Penh'));
        $session = $this->pendingSessionFor(['autoRecordStartedOn' => null]);
        $class = $session->studyClass;
        $enrollment = $class->enrollments()->first();

        app(InstructorClassService::class)->saveAttendance($class->teacher, $class->id, [
            'attendance_date' => '2026-08-21',
            'records' => [[
                'student_id' => $enrollment->student_id,
                'enrollment_id' => $enrollment->id,
                'status' => 'present',
                'note' => null,
            ]],
        ]);

        $this->assertSame('2026-08-21', $class->fresh()->auto_record_started_on->toDateString());
    }
}
