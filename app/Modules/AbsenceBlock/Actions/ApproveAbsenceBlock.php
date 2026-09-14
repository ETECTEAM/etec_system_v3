<?php

namespace App\Modules\AbsenceBlock\Actions;

use App\Models\StudentAttendance;
use App\Models\StudentAttendanceBlock;
use App\Models\User;
use App\Modules\AbsenceBlock\Services\AbsenceBlockAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admin approves a pending 'absence' block. Approval applies to EVERY open
 * 'absence' block for the same tel + course_id at once (the student may have
 * been locked from several classes in the course). Approved blocks no longer
 * lock attendance; the post-approval allowance starts.
 */
class ApproveAbsenceBlock
{
    public function __construct(private readonly AbsenceBlockAudit $audit) {}

    /** @return int number of blocks approved */
    public function handle(StudentAttendanceBlock $block, User $actor): int
    {
        if ($block->block_type !== StudentAttendanceBlock::TYPE_ABSENCE) {
            throw ValidationException::withMessages(['block' => 'Only an absence block can be approved here.']);
        }

        return DB::transaction(function () use ($block, $actor): int {
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
                    'is_approved' => true,
                    'approved_at' => now(),
                    'approved_by' => $actor->id,
                ]);
                $this->audit->log('absence_block.approved', $actor, [
                    'block_id' => $row->id,
                    'before' => $before,
                    'after' => $row->fresh()->toArray(),
                ]);
            }

            // Approved blocks stop locking - clear the flag on the rows they locked
            // (the recorded 'absent' status stands).
            StudentAttendance::query()
                ->whereIn('locked_block_id', $ids)
                ->update(['locked' => false, 'lock_reason' => null, 'locked_block_id' => null]);

            return $open->count();
        });
    }
}
