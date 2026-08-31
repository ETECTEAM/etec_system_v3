<?php

namespace App\Modules\AbsenceBlock\Services;

use App\Models\AttendanceRule;
use App\Models\StudyClass;

/**
 * Picks the active rule that governs a class (the spec's getRuleForClass), and
 * the effective limit that follows from it.
 *
 * period_type -> which classes a rule covers:
 *   both  -> every class
 *   week  -> weekday classes (no Sat/Sun in the term)
 *   month -> weekend classes (Sat or Sun in the term)
 * Newest active rule (highest id) wins when several match.
 */
class AbsenceRuleMatcher
{
    public function forClass(StudyClass $class, string $ruleType): ?AttendanceRule
    {
        $classPeriod = $this->isWeekendClass($class)
            ? AttendanceRule::PERIOD_MONTH
            : AttendanceRule::PERIOD_WEEK;

        return AttendanceRule::query()
            ->active()
            ->ofType($ruleType)
            ->whereIn('period_type', [AttendanceRule::PERIOD_BOTH, $classPeriod])
            ->orderByDesc('id')
            ->first();
    }

    public function absenceLimit(StudyClass $class): int
    {
        return $this->forClass($class, AttendanceRule::TYPE_ABSENCE)?->limit_count
            ?? (int) attendance_rule_setting('absence_block_threshold');
    }

    public function permissionWeeklyLimit(StudyClass $class): int
    {
        return $this->forClass($class, AttendanceRule::TYPE_PERMISSION)?->limit_count
            ?? (int) attendance_rule_setting('permission_weekly_limit');
    }

    public function isWeekendClass(StudyClass $class): bool
    {
        return (bool) array_intersect(['Saturday', 'Sunday'], $class->scheduleStudyDays());
    }
}
