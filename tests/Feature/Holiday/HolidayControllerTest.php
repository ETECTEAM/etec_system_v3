<?php

namespace Tests\Feature\Holiday;

use App\Models\ClassSession;
use App\Models\Holiday;
use App\Models\User;
use App\Modules\Holiday\Services\HolidayService;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

class HolidayControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAttendanceFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_create_a_multi_day_holiday_range(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post('/dashboard/holidays', [
                'name' => 'Pchum Ben Holiday',
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-03',
                'description' => 'School closed',
            ])
            ->assertRedirect();

        foreach (['2026-10-01', '2026-10-02', '2026-10-03'] as $date) {
            $this->assertDatabaseHas('holidays', [
                'date' => $date,
                'name' => 'Pchum Ben Holiday',
            ]);
        }

        $this->assertTrue(Holiday::isHoliday('2026-10-02'));
        $this->assertFalse(Holiday::isHoliday('2026-10-04'));
    }

    public function test_admin_can_create_selected_non_contiguous_holiday_dates(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post('/dashboard/holidays', [
                'name' => 'Selected School Holidays',
                'dates' => ['2026-10-01', '2026-10-03'],
                'description' => 'Two selected dates',
            ])
            ->assertRedirect();

        $this->assertTrue(Holiday::isHoliday('2026-10-01'));
        $this->assertFalse(Holiday::isHoliday('2026-10-02'));
        $this->assertTrue(Holiday::isHoliday('2026-10-03'));
    }

    public function test_holiday_service_skips_existing_unresolved_sessions_without_deleting_attendance(): void
    {
        $class = $this->makeStudyClass();
        $student = $this->makeStudent();
        $enrollment = $this->enroll($class, $student);
        $session = ClassSession::create([
            'study_class_id' => $class->id,
            'instructor_id' => $class->teacher_id,
            'session_date' => '2026-09-10',
            'scheduled_start' => '2026-09-10 09:00:00',
            'scheduled_end' => '2026-09-10 10:30:00',
            'status' => ClassSession::STATUS_PENDING,
        ]);

        \App\Models\StudentAttendance::create([
            'study_class_id' => $class->id,
            'student_enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'attendance_date' => '2026-09-10',
            'status' => 'present',
            'source' => \App\Models\StudentAttendance::SOURCE_MANUAL,
        ]);

        app(HolidayService::class)->saveRange([
            'name' => 'Constitution Day',
            'start_date' => '2026-09-10',
            'end_date' => null,
            'description' => null,
        ]);

        $this->assertSame(ClassSession::STATUS_SKIPPED, $session->fresh()->status);
        $this->assertDatabaseHas('student_attendances', [
            'student_enrollment_id' => $enrollment->id,
            'status' => 'present',
        ]);
    }
}
