<?php

namespace Tests\Feature\AbsenceBlock;

use App\Models\OfficialLeave;
use App\Models\StudentAttendanceBlock;
use App\Models\User;
use App\Modules\AbsenceBlock\Actions\ApproveAbsenceBlock;
use App\Modules\AbsenceBlock\Services\AbsenceBlockEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\AbsenceBlock\Concerns\BuildsAbsenceScenario;
use Tests\TestCase;

class AbsenceBlockEvaluatorTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAbsenceScenario;

    private AbsenceBlockEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Core\RoleSeeder::class);
        $this->evaluator = app(AbsenceBlockEvaluator::class);
        Carbon::setTestNow('2026-05-20 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    private function block(array $overrides = []): StudentAttendanceBlock
    {
        $course = $overrides['course'] ?? $this->makeCourseNamed('C');
        $student = $overrides['student'] ?? $this->studentWithPhone('012345678');

        return StudentAttendanceBlock::create(array_merge([
            'student_id' => $student->id,
            'student_tel' => $student->phone,
            'course_id' => $course->id,
            'block_type' => StudentAttendanceBlock::TYPE_ABSENCE,
            'is_approved' => false,
            'blocked_at' => now(),
            'cycle_start_date' => '2026-04-01',
        ], collect($overrides)->except(['course', 'student'])->all()));
    }

    public function test_no_block_is_unlocked(): void
    {
        $class = $this->weekdayClass();
        $student = $this->studentWithPhone('011000111');

        $state = $this->evaluator->evaluate($student->id, $class->id);

        $this->assertFalse($state->locked);
        $this->assertSame('none', $state->phase);
    }

    public function test_open_absence_block_soft_locks(): void
    {
        $course = $this->makeCourseNamed('X');
        $class = $this->weekdayClass($course);
        $student = $this->studentWithPhone('011000222');
        $b = $this->block(['course' => $course, 'student' => $student]);

        $state = $this->evaluator->evaluate($student->id, $class->id);

        $this->assertTrue($state->locked);
        $this->assertSame('soft', $state->phase);
        $this->assertSame($b->id, $state->blockId);
        $this->assertSame(StudentAttendanceBlock::REASON_SOFT, $state->reason);
    }

    public function test_open_hard_lock_outranks_an_absence_block(): void
    {
        $course = $this->makeCourseNamed('X');
        $class = $this->weekdayClass($course);
        $student = $this->studentWithPhone('011000333');
        $this->block(['course' => $course, 'student' => $student]);
        $this->block(['course' => $course, 'student' => $student, 'block_type' => StudentAttendanceBlock::TYPE_HARD_LOCK]);

        $state = $this->evaluator->evaluate($student->id, $class->id);

        $this->assertSame('hard', $state->phase);
        $this->assertSame(StudentAttendanceBlock::REASON_HARD, $state->reason);
    }

    public function test_approved_absence_block_is_post_approval_not_locked(): void
    {
        $course = $this->makeCourseNamed('X');
        $class = $this->weekdayClass($course);
        $student = $this->studentWithPhone('011000444');
        $b = $this->block(['course' => $course, 'student' => $student]);
        app(ApproveAbsenceBlock::class)->handle($b, User::factory()->create());

        $state = $this->evaluator->evaluate($student->id, $class->id);

        $this->assertFalse($state->locked);
        $this->assertSame('post_approval', $state->phase);
    }

    public function test_approved_official_leave_outranks_any_block(): void
    {
        $course = $this->makeCourseNamed('X');
        $class = $this->weekdayClass($course);
        $student = $this->studentWithPhone('011000555');
        $this->block(['course' => $course, 'student' => $student]);

        OfficialLeave::create([
            'student_id' => $student->id,
            'start_date' => '2026-05-19',
            'end_date' => '2026-05-21',
            'reason' => 'family',
            'status' => OfficialLeave::STATUS_APPROVED,
        ]);

        $this->assertFalse($this->evaluator->evaluate($student->id, $class->id)->locked);
    }
}
