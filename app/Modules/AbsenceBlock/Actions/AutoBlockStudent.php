<?php

namespace App\Modules\AbsenceBlock\Actions;

use App\Models\OfficialLeave;
use App\Models\Student;
use App\Models\StudentAttendanceBlock;
use App\Models\StudyClass;
use App\Models\User;
use App\Modules\AbsenceBlock\Services\AbsenceBlockAudit;
use App\Modules\AbsenceBlock\Services\AbsenceCounter;
use App\Modules\AbsenceBlock\Services\AbsenceCycleResolver;
use App\Modules\AbsenceBlock\Services\AbsenceRuleMatcher;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Raises the next block in the workflow after an attendance row has settled as
 * 'absent' for (student, class, date). Idempotent: a second call while a block
 * is already open is a no-op. Safe to call from the instructor save path, the
 * override path, the QR path and the auto-record cron.
 */
class AutoBlockStudent
{
    public function __construct(
        private readonly AbsenceCycleResolver $cycles,
        private readonly AbsenceRuleMatcher $rules,
        private readonly AbsenceCounter $counter,
        private readonly AbsenceBlockAudit $audit,
    ) {}

    public function handle(int $studentId, int $studyClassId, CarbonImmutable|string $date, ?User $actor = null): ?StudentAttendanceBlock
    {
        $student = Student::query()->find($studentId);
        $class = StudyClass::query()->find($studyClassId);

        if (! $student || ! $class || ! $class->course_id || $student->phone === null || $student->phone === '') {
            return null;
        }

        $tel = (string) $student->phone;
        $courseId = (int) $class->course_id;
        $onDate = CarbonImmutable::parse($date)->startOfDay();
        $cycleStart = $this->cycles->cycleStart($tel, $courseId);

        return DB::transaction(function () use ($student, $class, $tel, $courseId, $onDate, $cycleStart, $actor): ?StudentAttendanceBlock {
            $open = StudentAttendanceBlock::query()
                ->forCycleKey($tel, $courseId)
                ->open()
                ->lockForUpdate()
                ->get();

            if ($open->firstWhere('block_type', StudentAttendanceBlock::TYPE_HARD_LOCK)) {
                return null; // already hard-locked
            }

            $openAbsence = $open->firstWhere('block_type', StudentAttendanceBlock::TYPE_ABSENCE);

            $approvedAbsence = StudentAttendanceBlock::query()
                ->forCycleKey($tel, $courseId)
                ->ofType(StudentAttendanceBlock::TYPE_ABSENCE)
                ->where('is_approved', true)
                ->whereDate('cycle_start_date', $cycleStart->toDateString())
                ->orderBy('approved_at')
                ->first();

            // --- Phase 3: hard lock (post-approval allowance blown) ---
            if ($approvedAbsence) {
                $allowance = (int) attendance_rule_setting('post_approval_limit');
                $since = CarbonImmutable::parse($approvedAbsence->approved_at)->addDay()->startOfDay();
                $postApprovalAbsences = $this->counter->absenceDays($tel, $courseId, $since, $onDate);

                if ($postApprovalAbsences >= $allowance) {
                    $block = StudentAttendanceBlock::create([
                        'student_id' => $student->id,
                        'student_tel' => $tel,
                        'course_id' => $courseId,
                        'study_class_id' => $class->id,
                        'block_type' => StudentAttendanceBlock::TYPE_HARD_LOCK,
                        'is_approved' => false,
                        'blocked_at' => now(),
                        'admin_comment' => StudentAttendanceBlock::COMMENT_HARD_LOCK,
                        'cycle_start_date' => $cycleStart->toDateString(),
                    ]);

                    $this->applyLock($block, $tel, $courseId, $onDate, StudentAttendanceBlock::REASON_HARD);
                    $this->audit->log('hard_lock.auto_created', $actor, [
                        'block_id' => $block->id,
                        'after' => $block->toArray(),
                    ]);

                    return $block;
                }

                return null; // still within the allowance
            }

            // --- Phase 1: soft lock (monthly threshold reached) ---
            if (! $openAbsence) {
                [$winStart, $winEnd] = $this->cycles->countWindow($tel, $courseId, $onDate);
                $absences = $this->counter->absenceDays($tel, $courseId, $winStart, $winEnd);

                if ($absences >= $this->rules->absenceLimit($class)) {
                    $block = StudentAttendanceBlock::create([
                        'student_id' => $student->id,
                        'student_tel' => $tel,
                        'course_id' => $courseId,
                        'study_class_id' => $class->id,
                        'block_type' => StudentAttendanceBlock::TYPE_ABSENCE,
                        'is_approved' => false,
                        'blocked_at' => now(),
                        'cycle_start_date' => $cycleStart->toDateString(),
                    ]);

                    $this->applyLock($block, $tel, $courseId, $winStart, StudentAttendanceBlock::REASON_SOFT);
                    $this->audit->log('absence_block.auto_created', $actor, [
                        'block_id' => $block->id,
                        'after' => $block->toArray(),
                    ]);

                    return $block;
                }
            }

            return $openAbsence;
        });
    }

    /**
     * Force every not-yet-official-leave attendance row for this tel+course from
     * $from forward to a locked 'absent' pointing at $block.
     */
    private function applyLock(StudentAttendanceBlock $block, string $tel, int $courseId, CarbonImmutable $from, string $reason): void
    {
        $rows = \App\Models\StudentAttendance::query()
            ->select('student_attendances.*')
            ->join('students', 'students.id', '=', 'student_attendances.student_id')
            ->join('study_classes', 'study_classes.id', '=', 'student_attendances.study_class_id')
            ->where('students.phone', $tel)
            ->where('study_classes.course_id', $courseId)
            ->whereDate('student_attendances.attendance_date', '>=', $from->toDateString())
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('official_leaves')
                    ->whereColumn('official_leaves.student_id', 'student_attendances.student_id')
                    ->where('official_leaves.status', OfficialLeave::STATUS_APPROVED)
                    ->whereColumn('official_leaves.start_date', '<=', 'student_attendances.attendance_date')
                    ->whereColumn('official_leaves.end_date', '>=', 'student_attendances.attendance_date');
            })
            ->get();

        foreach ($rows as $row) {
            $row->update([
                'status' => 'absent',
                'locked' => true,
                'lock_reason' => $reason,
                'locked_block_id' => $block->id,
            ]);
        }
    }
}
