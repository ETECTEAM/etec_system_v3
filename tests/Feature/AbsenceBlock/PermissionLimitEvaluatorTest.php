<?php

namespace Tests\Feature\AbsenceBlock;

use App\Models\AttendanceRule;
use App\Models\OfficialLeave;
use App\Modules\AbsenceBlock\Services\PermissionLimitEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\AbsenceBlock\Concerns\BuildsAbsenceScenario;
use Tests\TestCase;

class PermissionLimitEvaluatorTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAbsenceScenario;

    private PermissionLimitEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Core\RoleSeeder::class);
        $this->evaluator = app(PermissionLimitEvaluator::class);
        Carbon::setTestNow('2026-05-20 12:00:00'); // a Wednesday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    public function test_first_permission_of_the_week_is_allowed(): void
    {
        $class = $this->weekdayClass();
        $student = $this->studentWithPhone('011010101');

        $result = $this->evaluator->resolve($student->id, $class, '2026-05-20');

        $this->assertSame('permission', $result['status']);
        $this->assertFalse($result['counted_as_absence']);
    }

    public function test_second_permission_in_the_same_week_is_counted_as_absence(): void
    {
        $class = $this->weekdayClass();
        $student = $this->studentWithPhone('011020202');
        $this->record($class, $student, '2026-05-18', 'permission'); // Monday, same ISO week

        $result = $this->evaluator->resolve($student->id, $class, '2026-05-20');

        $this->assertSame('absent', $result['status']);
        $this->assertSame(PermissionLimitEvaluator::NOTE_CONVERTED, $result['note']);
        $this->assertTrue($result['counted_as_absence']);
    }

    public function test_a_permission_rule_limit_overrides_the_default(): void
    {
        AttendanceRule::create([
            'rule_type' => AttendanceRule::TYPE_PERMISSION,
            'limit_count' => 2,
            'period_type' => AttendanceRule::PERIOD_BOTH,
            'start_date' => '2026-04-01',
            'is_active' => true,
        ]);

        $class = $this->weekdayClass();
        $student = $this->studentWithPhone('011030303');
        $this->record($class, $student, '2026-05-18', 'permission');

        $this->assertSame('permission', $this->evaluator->resolve($student->id, $class, '2026-05-20')['status']);
    }

    public function test_approved_official_leave_is_always_forced_as_permission(): void
    {
        $class = $this->weekdayClass();
        $student = $this->studentWithPhone('011040404');
        $this->record($class, $student, '2026-05-18', 'permission');

        OfficialLeave::create([
            'student_id' => $student->id,
            'start_date' => '2026-05-20',
            'end_date' => '2026-05-20',
            'reason' => 'official',
            'status' => OfficialLeave::STATUS_APPROVED,
        ]);

        $result = $this->evaluator->resolve($student->id, $class, '2026-05-20');
        $this->assertSame('permission', $result['status']);
        $this->assertFalse($result['counted_as_absence']);
    }
}
