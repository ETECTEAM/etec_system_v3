<?php

namespace Tests\Unit\Attendance;

use App\Models\ClassSession;
use App\Models\Holiday;
use App\Models\Notification;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

class HolidayAttendanceDigestTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAttendanceFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Carbon::setTestNow(null);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);

        parent::tearDown();
    }

    public function test_holiday_does_not_send_attendance_digest(): void
    {
        $now = Carbon::parse('2026-08-18 18:00:00', 'Asia/Phnom_Penh');
        Carbon::setTestNow($now);

        Holiday::create(['date' => '2026-08-18', 'name' => 'School Holiday']);

        $class = $this->makeStudyClass();
        ClassSession::create([
            'study_class_id' => $class->id,
            'instructor_id' => $class->teacher_id,
            'session_date' => '2026-08-18',
            'scheduled_start' => '2026-08-18 09:00:00',
            'scheduled_end' => '2026-08-18 10:30:00',
            'status' => ClassSession::STATUS_AUTO_RECORDED,
            'recorded_at' => '2026-08-18 09:30:00',
        ]);

        Artisan::call('attendance:send-digest');

        $this->assertSame(0, Notification::query()->where('type', 'attendance_digest')->count());
    }
}
