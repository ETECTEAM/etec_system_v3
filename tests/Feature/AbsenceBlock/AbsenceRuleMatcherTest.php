<?php

namespace Tests\Feature\AbsenceBlock;

use App\Models\AttendanceRule;
use App\Modules\AbsenceBlock\Services\AbsenceRuleMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\AbsenceBlock\Concerns\BuildsAbsenceScenario;
use Tests\TestCase;

class AbsenceRuleMatcherTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAbsenceScenario;

    private AbsenceRuleMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Core\RoleSeeder::class);
        $this->matcher = app(AbsenceRuleMatcher::class);
    }

    public function test_week_rule_covers_weekday_classes_only(): void
    {
        $this->activeAbsenceRule(['period_type' => AttendanceRule::PERIOD_WEEK, 'limit_count' => 5]);

        $this->assertNotNull($this->matcher->forClass($this->weekdayClass(), AttendanceRule::TYPE_ABSENCE));
        $this->assertNull($this->matcher->forClass($this->weekendClass(), AttendanceRule::TYPE_ABSENCE));
    }

    public function test_month_rule_covers_weekend_classes_only(): void
    {
        $this->activeAbsenceRule(['period_type' => AttendanceRule::PERIOD_MONTH, 'limit_count' => 4]);

        $this->assertNotNull($this->matcher->forClass($this->weekendClass(), AttendanceRule::TYPE_ABSENCE));
        $this->assertNull($this->matcher->forClass($this->weekdayClass(), AttendanceRule::TYPE_ABSENCE));
    }

    public function test_both_rule_covers_every_class(): void
    {
        $this->activeAbsenceRule(['period_type' => AttendanceRule::PERIOD_BOTH]);

        $this->assertNotNull($this->matcher->forClass($this->weekdayClass(), AttendanceRule::TYPE_ABSENCE));
        $this->assertNotNull($this->matcher->forClass($this->weekendClass(), AttendanceRule::TYPE_ABSENCE));
    }

    public function test_newest_active_matching_rule_wins(): void
    {
        $this->activeAbsenceRule(['period_type' => AttendanceRule::PERIOD_BOTH, 'limit_count' => 3]);
        $this->activeAbsenceRule(['period_type' => AttendanceRule::PERIOD_WEEK, 'limit_count' => 7]);

        $this->assertSame(7, $this->matcher->absenceLimit($this->weekdayClass()));
    }

    public function test_inactive_rules_are_ignored_and_fall_back_to_settings(): void
    {
        $this->activeAbsenceRule(['is_active' => false, 'limit_count' => 9]);

        // Seeded default absence_block_threshold = 3.
        $this->assertSame(3, $this->matcher->absenceLimit($this->weekdayClass()));
    }
}
