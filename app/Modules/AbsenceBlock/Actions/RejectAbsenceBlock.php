<?php

namespace App\Modules\AbsenceBlock\Actions;

use App\Models\StudentAttendance;
use App\Models\StudentAttendanceBlock;
use App\Models\User;
use App\Modules\AbsenceBlock\Services\AbsenceBlockAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admin rejects a pending 'absence' block. Reject CLEARS the block: rejected_at
 * is stamped, the student is unlocked immediately, and the recorded absences
 * keep accruing - so a fresh block can be raised again at the next threshold
 * hit. Applies to every open 'absence' block for the same tel + course_id.
 */
class RejectAbsenceBlock
{
    public function __construct(private readonly AbsenceBlockAudit $audit) {}

    /** @return int number of blocks cleared */
    public function handle(StudentAttendanceBlock $block, User $actor, ?string $comment = null): int
    {
        if ($block->block_type !== StudentAttendanceBlock::TYPE_ABSENCE) {
            throw ValidationException::withMessages(['block' => 'Only an absence block can be rejected here.']);
        }

        return DB::transaction(function () use ($block, $actor, $comment): int {
            $open = StudentAttendanceBlock::query()
                ->forCycleKey($block->student_tel, $block->course_id)
                ->ofType(StudentAttendanceBlock::TYPE_ABSENCE)
                ->open()
                ->lockForUpdate()
                ->get();

            if ($open->isEmpty()) {
                throw ValidationException::withMessages(['block' => 'This block is no longer pending.']);
            }

            $ids = $open->pluck('id')->all();

            foreach ($open as $row) {
                $before = $row->toArray();
                $row->update([
                    'rejected_at' => now(),
                    'admin_comment' => $comment,
                ]);
                $this->audit->log('absence_block.rejected', $actor, [
                    'block_id' => $row->id,
                    'before' => $before,
                    'after' => $row->fresh()->toArray(),
                ]);
            }

            StudentAttendance::query()
                ->whereIn('locked_block_id', $ids)
                ->update(['locked' => false, 'lock_reason' => null, 'locked_block_id' => null]);

            return $open->count();
        });
    }
}
