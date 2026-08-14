<?php

namespace Tests\Unit\Attendance;

use App\Models\ClassSession;
use App\Models\Holiday;
use App\Modules\Attendance\Actions\GenerateClassSessions;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Tests\Unit\Attendance\Concerns\CreatesAttendanceFixtures;

class GenerateClassSessionsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAttendanceFixtures;

    private GenerateClassSessions $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->action = app(GenerateClassSessions::class);
    }

    // A Tuesday, so "Mon & Tue" classes meet and "Wed & Thu" ones don't.
    private function aTuesday(): Carbon
    {
        return Carbon::parse('2026-08-18', 'Asia/Phnom_Penh');
    }

    public function test_creates_a_pending_session_for_a_class_meeting_that_weekday(): void
    {
        $class = $this->makeStudyClass(['term' => $this->makeTerm('Mon & Tue')]);
        $this->enroll($class, $this->makeStudent());

        $this->action->handle($this->aTuesday());

        $this->assertDatabaseHas('class_sessions', [
            'study_class_id' => $class->id,
            'session_date' => '2026-08-18',
            'status' => ClassSession::STATUS_PENDING,
            'instructor_id' => $class->teacher_id,
        ]);
    }

    public function test_does_not_create_a_session_for_a_class_not_meeting_that_weekday(): void
    {
        $class = $this->makeStudyClass(['term' => $this->makeTerm('Wed & Thu')]);
        $this->enroll($class, $this->makeStudent());

        $this->action->handle($this->aTuesday());

        $this->assertDatabaseMissing('class_sessions', ['study_class_id' => $class->id]);
    }

    public function test_class_with_no_active_students_is_skipped_not_pending(): void
    {
        $class = $this->makeStudyClass(['term' => $this->makeTerm('Mon & Tue')]);
        // No enrollment created.

        $this->action->handle($this->aTuesday());

        $this->assertDatabaseHas('class_sessions', [
            'study_class_id' => $class->id,
            'status' => ClassSession::STATUS_SKIPPED,
        ]);
    }

    public function test_holiday_gets_no_sessions_at_all(): void
    {
        Holiday::create(['date' => '2026-08-18', 'name' => 'Test Holiday']);

        $class = $this->makeStudyClass(['term' => $this->makeTerm('Mon & Tue')]);
        $this->enroll($class, $this->makeStudent());

        $this->action->handle($this->aTuesday());

        $this->assertDatabaseMissing('class_sessions', ['study_class_id' => $class->id]);
    }

    public function test_scheduled_times_land_on_the_correct_wall_clock_hour_in_phnom_penh(): void
    {
        $class = $this->makeStudyClass([
            'term' => $this->makeTerm('Mon & Tue'),
            'time' => $this->makeTime('09:00 AM - 10:30 AM'),
        ]);
        $this->enroll($class, $this->makeStudent());

        $this->action->handle($this->aTuesday());

        $session = ClassSession::where('study_class_id', $class->id)->firstOrFail();

        $this->assertSame('2026-08-18 09:00', $session->scheduled_start->timezone('Asia/Phnom_Penh')->format('Y-m-d H:i'));
        $this->assertSame('2026-08-18 10:30', $session->scheduled_end->timezone('Asia/Phnom_Penh')->format('Y-m-d H:i'));
    }

    public function test_shared_class_routes_each_weekday_to_the_instructor_who_covers_it(): void
    {
        $owner = $this->makeInstructor();
        $coInstructor = $this->makeInstructor();

        $class = $this->makeStudyClass([
            'teacher' => $owner,
            'term' => $this->makeTerm('Wed & Thu'),
        ]);
        $this->enroll($class, $this->makeStudent());

        // Owner keeps Wed & Thu (the class's own term); co-instructor takes Mon & Tue -
        // mirrors ShareClassWithInstructor's split.
        $class->instructors()->sync([
            $owner->id => ['term_id' => $class->term_id, 'time_id' => $class->time_id],
            $coInstructor->id => ['term_id' => $this->makeTerm('Mon & Tue')->id, 'time_id' => $class->time_id],
        ]);

        $this->action->handle($this->aTuesday());

        $this->assertDatabaseHas('class_sessions', [
            'study_class_id' => $class->id,
            'session_date' => '2026-08-18',
            'instructor_id' => $coInstructor->id,
        ]);
    }
}
