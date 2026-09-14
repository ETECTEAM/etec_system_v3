<?php

namespace App\Modules\AbsenceBlock\Services;

use App\Models\Student;
use App\Models\StudentAttendanceBlock;
use App\Models\StudyClass;
use App\Modules\AbsenceBlock\Support\LockState;
use App\Modules\Attendance\Queries\HasApprovedPermission;
use App\Modules\OfficialLeave\Queries\HasApprovedOfficialLeave;
use Carbon\CarbonImmutable;

/**
 * Decides whether a student is locked at attendance time, and why. This is the
 * single source of truth read by every attendance write path and by the
 * instructor UI. Only *open* blocks (is_approved = 0 AND rejected_at IS NULL)
 * lock; approved blocks never lock.
 */
class AbsenceBlockEvaluator
{
    public function __construct(
        private readonly AbsenceCycleResolver $cycles,
        private readonly HasApprovedOfficialLeave $officialLeave,
        private readonly HasApprovedPermission $approvedPermission,
    ) {}

    public function evaluate(int $studentId, int $studyClassId, CarbonImmutable|string|null $date = null): LockState
    {
        $date = CarbonImmutable::parse($date ?? now())->startOfDay();

        // Approved official leave / manual permission outranks any block.
        if ($this->officialLeave->handle($studentId, $date)
            || $this->approvedPermission->handle($studentId, $studyClassId, $date)) {
            return LockState::unlocked();
        }

        $student = Student::query()->find($studentId);
        $courseId = (int) StudyClass::query()->whereKey($studyClassId)->value('course_id');

        if (! $student || $student->phone === null || $student->phone === '' || $courseId === 0) {
            return LockState::unlocked();
        }

        $tel = (string) $student->phone;

        $hardLock = StudentAttendanceBlock::query()
            ->forCycleKey($tel, $courseId)
            ->ofType(StudentAttendanceBlock::TYPE_HARD_LOCK)
            ->open()
            ->latest('id')
            ->first();

        if ($hardLock) {
            return LockState::locked('hard', StudentAttendanceBlock::REASON_HARD, $hardLock->id);
        }

        $absenceBlock = StudentAttendanceBlock::query()
            ->forCycleKey($tel, $courseId)
            ->ofType(StudentAttendanceBlock::TYPE_ABSENCE)
            ->open()
            ->latest('id')
            ->first();

        if ($absenceBlock) {
            return LockState::locked('soft', StudentAttendanceBlock::REASON_SOFT, $absenceBlock->id);
        }

        // An approved absence block for the current cycle => post-approval
        // allowance is running: not locked, but flagged so the UI can hint.
        $approvedThisCycle = StudentAttendanceBlock::query()
            ->forCycleKey($tel, $courseId)
            ->ofType(StudentAttendanceBlock::TYPE_ABSENCE)
            ->where('is_approved', true)
            ->whereDate('cycle_start_date', $this->cycles->cycleStart($tel, $courseId)->toDateString())
            ->exists();

        return LockState::unlocked($approvedThisCycle ? 'post_approval' : 'none');
    }

    /**
     * Batch variant for the instructor roster.
     *
     * @param  array<int>  $studentIds
     * @return array<int, LockState>
     */
    public function lockStateForRoster(int $studyClassId, array $studentIds, CarbonImmutable|string|null $date = null): array
    {
        $out = [];

        foreach (array_unique($studentIds) as $studentId) {
            $out[(int) $studentId] = $this->evaluate((int) $studentId, $studyClassId, $date);
        }

        return $out;
    }
}
