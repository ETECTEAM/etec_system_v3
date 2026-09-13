<?php

namespace App\Modules\Attendance\Actions;

use App\Models\InstructorAttendanceBlock;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ApproveInstructorAttendanceBlock
{
    public function handle(InstructorAttendanceBlock $block, User $actor): void
    {
        if (! in_array($block->status, InstructorAttendanceBlock::BLOCKING_STATUSES, true)) {
            throw ValidationException::withMessages([
                'block' => 'This block is no longer pending.',
            ]);
        }

        $block->update([
            'status' => InstructorAttendanceBlock::STATUS_APPROVED_UNBLOCK,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ]);
    }
}
