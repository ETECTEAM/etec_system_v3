<?php

namespace Tests\Feature\AbsenceBlock;

use App\Models\StudentAttendance;
use App\Models\StudentAttendanceBlock;
use App\Models\User;
use App\Modules\AbsenceBlock\Actions\ApproveAbsenceBlock;
use App\Modules\AbsenceBlock\Actions\AutoBlockStudent;
use App\Modules\AbsenceBlock\Actions\RejectAbsenceBlock;
use App\Modules\AbsenceBlock\Actions\UnlockHardLock;
use App\Modules\AbsenceBlock\Services\AbsenceCycleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\Feature\AbsenceBlock\Concerns\BuildsAbsenceScenario;
use Tests\TestCase;

class AbsenceBlockActionsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAbsenceScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Core\RoleSeeder::class);
        Carbon::setTestNow('2026-05-20 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    private function softLock(string $phone = '011abcabc'): StudentAttendanceBlock
    {
        $this->activeAbsenceRule(['limit_count' => 3]);
        $course = $this->makeCourseNamed('C');
        $classA = $this->weekdayClass($course);
        $classB = $this->makeStudyClass(['course' => $course, 'term' => $this->makeTerm('Wed & Thu')]);
        $student = $this->studentWithPhone($phone);

        $this->record($classA, $student, '2026-05-04');
        $this->record($classA, $student, '2026-05-11');
        $this->record($classA, $student, '2026-05-18');
        $this->record($classB, $student, '2026-05-19');

        app(AutoBlockStudent::class)->handle($student->id, $classA->id, '2026-05-18');
        app(AutoBlockStudent::class)->handle($student->id, $classB->id, '2026-05-19');

        return StudentAttendanceBlock::query()->ofType('absence')->open()->latest('id')->firstOrFail();
    }

    public function test_approve_clears_every_open_absence_block_for_the_cycle_key_and_unlocks_rows(): void
    {
        $block = $this->softLock();
        // One block per tel+course cycle covers absences from every class in the course.
        $this->assertGreaterThanOrEqual(1, StudentAttendanceBlock::query()->ofType('absence')->open()->count());
        $this->assertGreaterThanOrEqual(4, StudentAttendance::where('locked', true)->count());

        $count = app(ApproveAbsenceBlock::class)->handle($block, User::factory()->create());

        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertSame(0, StudentAttendanceBlock::query()->ofType('absence')->open()->count());
        $this->assertSame(0, StudentAttendance::where('locked', true)->count());
    }

    public function test_reject_clears_the_block_and_a_fresh_one_can_form_later(): void
    {
        $block = $this->softLock('011rejrej');

        app(RejectAbsenceBlock::class)->handle($block, User::factory()->create(), 'not this time');

        $this->assertNotNull($block->fresh()->rejected_at);
        $this->assertSame(0, StudentAttendance::where('locked', true)->count());

        // A later absence re-triggers a brand-new pending block.
        $student = $block->student;
        $class = $this->weekdayClass(\App\Models\Course::find($block->course_id));
        $this->record($class, $student, '2026-05-26');
        $fresh = app(AutoBlockStudent::class)->handle($student->id, $class->id, '2026-05-26');

        $this->assertNotNull($fresh);
        $this->assertNotSame($block->id, $fresh->id);
    }

    public function test_unlock_hard_lock_marks_it_approved_and_moves_the_cycle_start(): void
    {
        $course = $this->makeCourseNamed('C');
        $student = $this->studentWithPhone('011hardlk');
        $hard = StudentAttendanceBlock::create([
            'student_id' => $student->id,
            'student_tel' => $student->phone,
            'course_id' => $course->id,
            'block_type' => StudentAttendanceBlock::TYPE_HARD_LOCK,
            'is_approved' => false,
            'blocked_at' => now(),
            'admin_comment' => StudentAttendanceBlock::COMMENT_HARD_LOCK,
            'cycle_start_date' => '2026-04-01',
        ]);

        app(UnlockHardLock::class)->handle($hard, User::factory()->create());

        $hard->refresh();
        $this->assertTrue($hard->is_approved);
        $this->assertSame(StudentAttendanceBlock::COMMENT_UNLOCKED, $hard->admin_comment);
        $this->assertSame(
            $hard->approved_at->toDateString(),
            app(AbsenceCycleResolver::class)->cycleStart($student->phone, $course->id)->toDateString(),
        );
    }

    public function test_unlock_rejects_a_non_hard_lock_block(): void
    {
        $block = $this->softLock('011nothrd');

        $this->expectException(ValidationException::class);
        app(UnlockHardLock::class)->handle($block, User::factory()->create());
    }
}
