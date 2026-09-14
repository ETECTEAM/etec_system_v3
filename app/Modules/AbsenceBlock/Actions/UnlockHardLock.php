<?php

namespace App\Modules\AbsenceBlock\Actions;

use App\Models\StudentAttendance;
use App\Models\StudentAttendanceBlock;
use App\Models\User;
use App\Modules\AbsenceBlock\Services\AbsenceBlockAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Super admin clears a hard lock. Marks it approved with the fixed
 * "Unlocked by super admin" comment; its approved_at becomes the new cycle
 * anchor for that tel + course_id (see AbsenceCycleResolver), so counting
 * restarts from zero. The student's future rows are unlocked.
 */
class UnlockHardLock
{
    public function __construct(private readonly AbsenceBlockAudit $audit) {}

    public function handle(StudentAttendanceBlock $block, User $actor): void
    {
        if ($block->block_type !== StudentAttendanceBlock::TYPE_HARD_LOCK) {
            throw ValidationException::withMessages(['block' => 'This block is not a hard lock.']);
        }

        DB::transaction(function () use ($block, $actor): void {
            $fresh = StudentAttendanceBlock::query()->lockForUpdate()->find($block->id);

            if (! $fresh || ! $fresh->isOpen()) {
                throw ValidationException::withMessages(['block' => 'This hard lock is no longer open.']);
            }

            $before = $fresh->toArray();

            $fresh->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => $actor->id,
                'admin_comment' => StudentAttendanceBlock::COMMENT_UNLOCKED,
            ]);

            // Unlock this student's rows locked by the hard lock (and anything the
            // earlier soft block still held) from today forward. Past rows keep
            // their recorded history.
            StudentAttendance::query()
                ->join('students', 'students.id', '=', 'student_attendances.student_id')
                ->join('study_classes', 'study_classes.id', '=', 'student_attendances.study_class_id')
                ->where('students.phone', $fresh->student_tel)
                ->where('study_classes.course_id', $fresh->course_id)
                ->whereDate('student_attendances.attendance_date', '>=', now()->toDateString())
                ->where('student_attendances.locked', true)
                ->update([
                    'student_attendances.locked' => false,
                    'student_attendances.lock_reason' => null,
                    'student_attendances.locked_block_id' => null,
                ]);

            $this->audit->log('hard_lock.unlocked', $actor, [
                'block_id' => $fresh->id,
                'before' => $before,
                'after' => $fresh->fresh()->toArray(),
            ]);
        });
    }
}
