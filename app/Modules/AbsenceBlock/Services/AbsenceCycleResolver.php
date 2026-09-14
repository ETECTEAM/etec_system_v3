<?php

namespace App\Modules\AbsenceBlock\Services;

use App\Models\AttendanceRule;
use App\Models\StudentAttendanceBlock;
use Carbon\CarbonImmutable;

/**
 * Resolves the absence "cycle" for a (tel + course) pair.
 *
 * Cycle start = max(
 *   attendance_rule_setting('cycle_anchor_date')  (default 2026-04-01),
 *   newest active absence rule's start_date,
 *   latest approved hard-lock unlock's approved_at for this tel+course
 * ).
 *
 * The soft-lock counting window is the calendar month of the attendance date,
 * clamped so it never reaches before the cycle start.
 */
class AbsenceCycleResolver
{
    public function cycleStart(string $tel, int $courseId): CarbonImmutable
    {
        $start = CarbonImmutable::parse(
            attendance_rule_setting('cycle_anchor_date') ?: '2026-04-01'
        )->startOfDay();

        $ruleStart = AttendanceRule::query()
            ->active()
            ->ofType(AttendanceRule::TYPE_ABSENCE)
            ->orderByDesc('id')
            ->value('start_date');

        if ($ruleStart) {
            $start = $start->max(CarbonImmutable::parse($ruleStart)->startOfDay());
        }

        $lastUnlock = StudentAttendanceBlock::query()
            ->forCycleKey($tel, $courseId)
            ->ofType(StudentAttendanceBlock::TYPE_HARD_LOCK)
            ->where('is_approved', true)
            ->whereNotNull('approved_at')
            ->max('approved_at');

        if ($lastUnlock) {
            $start = $start->max(CarbonImmutable::parse($lastUnlock)->startOfDay());
        }

        return $start;
    }

    /**
     * [windowStart, windowEnd] for counting soft-lock absences on $onDate.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function countWindow(string $tel, int $courseId, CarbonImmutable|string|null $onDate = null): array
    {
        $cycleStart = $this->cycleStart($tel, $courseId);
        $on = CarbonImmutable::parse($onDate ?? now())->startOfDay();

        $monthStart = $on->startOfMonth();
        $windowStart = $monthStart->greaterThan($cycleStart) ? $monthStart : $cycleStart;

        return [$windowStart, $on->endOfMonth()];
    }
}
