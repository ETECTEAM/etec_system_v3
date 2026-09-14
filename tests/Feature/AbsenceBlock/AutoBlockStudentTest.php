<?php

namespace Tests\Feature\AbsenceBlock;

use App\Models\StudentAttendance;
use App\Models\StudentAttendanceBlock;
use App\Modules\AbsenceBlock\Actions\ApproveAbsenceBlock;
use App\Modules\AbsenceBlock\Actions\AutoBlockStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\AbsenceBlock\Concerns\BuildsAbsenceScenario;
use Tests\TestCase;

class AutoBlockStudentTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAbsenceScenario;

    private AutoBlockStudent $autoBlock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Core\RoleSeeder::class);
        $this->autoBlock = app(AutoBlockStudent::class);
        Carbon::setTestNow('2026-05-20 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    public function test_reaching_the_threshold_raises_a_pending_absence_block_and_locks_the_rows(): void
    {
        $this->activeAbsenceRule(['limit_count' => 3]);
        $class = $this->weekdayClass();
        $student = $this->studentWithPhone('011111222');

        foreach (['2026-05-04', '2026-05-11', '2026-05-18'] as $d) {
            $this->record($class, $student, $d);
        }

        $block = $this->autoBlock->handle($student->id, $class->id, '2026-05-18');

        $this->assertNotNull($block);
        $this->assertSame(StudentAttendanceBlock::TYPE_ABSENCE, $block->block_type);
        $this->assertFalse($block->is_approved);
        $this->assertSame(3, StudentAttendance::where('locked', true)->count());
        $this->assertSame(StudentAttendanceBlock::REASON_SOFT, StudentAttendance::where('locked', true)->first()->lock_reason);
    }

    public function test_below_the_threshold_raises_nothing(): void
    {
        $this->activeAbsenceRule(['limit_count' => 3]);
        $class = $this->weekdayClass();
        $student = $this->studentWithPhone('011111333');

        foreach (['2026-05-04', '2026-05-11'] as $d) {
            $this->record($class, $student, $d);
        }

        $this->assertNull($this->autoBlock->handle($student->id, $class->id, '2026-05-11'));
        $this->assertSame(0, StudentAttendanceBlock::count());
    }

    public function test_it_is_idempotent_while_a_block_is_open(): void
    {
        $this->activeAbsenceRule(['limit_count' => 3]);
        $class = $this->weekdayClass();
        $student = $this->studentWithPhone('011111444');

        foreach (['2026-05-04', '2026-05-11', '2026-05-18'] as $d) {
            $this->record($class, $student, $d);
        }

        $this->autoBlock->handle($student->id, $class->id, '2026-05-18');
        $this->autoBlock->handle($student->id, $class->id, '2026-05-18');

        $this->assertSame(1, StudentAttendanceBlock::count());
    }

    public function test_absences_are_counted_by_tel_plus_course_across_classes(): void
    {
        $this->activeAbsenceRule(['limit_count' => 3]);
        $course = $this->makeCourseNamed('Shared');
        $classA = $this->weekdayClass($course);
        $classB = $this->makeStudyClass(['course' => $course, 'term' => $this->makeTerm('Wed & Thu')]);
        $student = $this->studentWithPhone('011999888');

        $this->record($classA, $student, '2026-05-04');
        $this->record($classA, $student, '2026-05-11');
        $this->record($classB, $student, '2026-05-18');

        $block = $this->autoBlock->handle($student->id, $classB->id, '2026-05-18');

        $this->assertNotNull($block);
        $this->assertSame($course->id, $block->course_id);
    }

    public function test_post_approval_overflow_raises_a_hard_lock(): void
    {
        $this->activeAbsenceRule(['limit_count' => 3]);
        $class = $this->weekdayClass();
        $student = $this->studentWithPhone('011222444');

        foreach (['2026-05-04', '2026-05-11', '2026-05-18'] as $d) {
            $this->record($class, $student, $d);
        }
        $block = $this->autoBlock->handle($student->id, $class->id, '2026-05-18');
        app(ApproveAbsenceBlock::class)->handle($block, User::factory()->create());

        // Two more absences after approval (post_approval_limit = 2).
        $this->record($class, $student, '2026-05-25');
        $this->autoBlock->handle($student->id, $class->id, '2026-05-25');
        $this->record($class, $student, '2026-06-01');
        $hard = $this->autoBlock->handle($student->id, $class->id, '2026-06-01');

        $this->assertNotNull($hard);
        $this->assertSame(StudentAttendanceBlock::TYPE_HARD_LOCK, $hard->block_type);
        $this->assertSame(StudentAttendanceBlock::COMMENT_HARD_LOCK, $hard->admin_comment);
    }
}
