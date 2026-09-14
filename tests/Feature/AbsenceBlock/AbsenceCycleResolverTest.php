<?php

namespace Tests\Feature\AbsenceBlock;

use App\Models\StudentAttendanceBlock;
use App\Modules\AbsenceBlock\Services\AbsenceCycleResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\AbsenceBlock\Concerns\BuildsAbsenceScenario;
use Tests\TestCase;

class AbsenceCycleResolverTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAbsenceScenario;

    private AbsenceCycleResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Core\RoleSeeder::class);
        $this->resolver = app(AbsenceCycleResolver::class);
    }

    public function test_defaults_to_the_anchor_date(): void
    {
        $this->assertSame('2026-04-01', $this->resolver->cycleStart('011000000', 1)->toDateString());
    }

    public function test_a_later_rule_start_date_moves_the_cycle_start(): void
    {
        $this->activeAbsenceRule(['start_date' => '2026-06-15']);

        $this->assertSame('2026-06-15', $this->resolver->cycleStart('011000000', 1)->toDateString());
    }

    public function test_a_later_hard_lock_unlock_moves_the_cycle_start(): void
    {
        $course = $this->makeCourseNamed('C');
        $student = $this->studentWithPhone('011555777');

        StudentAttendanceBlock::create([
            'student_id' => $student->id,
            'student_tel' => '011555777',
            'course_id' => $course->id,
            'block_type' => StudentAttendanceBlock::TYPE_HARD_LOCK,
            'is_approved' => true,
            'blocked_at' => '2026-07-01 09:00:00',
            'approved_at' => '2026-07-20 10:00:00',
            'admin_comment' => StudentAttendanceBlock::COMMENT_UNLOCKED,
            'cycle_start_date' => '2026-04-01',
        ]);

        $this->assertSame('2026-07-20', $this->resolver->cycleStart('011555777', $course->id)->toDateString());
    }

    public function test_count_window_is_the_calendar_month_clamped_to_the_cycle_start(): void
    {
        $this->activeAbsenceRule(['start_date' => '2026-05-10']);

        [$start, $end] = $this->resolver->countWindow('011000000', 1, CarbonImmutable::parse('2026-05-22'));
        $this->assertSame('2026-05-10', $start->toDateString());
        $this->assertSame('2026-05-31', $end->toDateString());

        [$start2] = $this->resolver->countWindow('011000000', 1, CarbonImmutable::parse('2026-06-05'));
        $this->assertSame('2026-06-01', $start2->toDateString());
    }
}
