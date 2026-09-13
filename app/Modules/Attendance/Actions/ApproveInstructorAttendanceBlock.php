<?php

namespace App\Modules\Attendance\Actions;

use App\Models\InstructorAttendanceBlock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveInstructorAttendanceBlock
{
    public function __construct(
        private readonly AutoFillTriggeringSessionFromLastWeek $autoFillTriggeringSession,
    ) {}

    public function handle(InstructorAttendanceBlock $block, User $actor): void
    {
        if (! in_array($block->status, InstructorAttendanceBlock::BLOCKING_STATUSES, true)) {
            throw ValidationException::withMessages([
                'block' => 'This block is no longer pending.',
            ]);
        }

        DB::transaction(function () use ($block, $actor): void {
            $block->update([
                'status' => InstructorAttendanceBlock::STATUS_APPROVED_UNBLOCK,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            // The class that caused the block doesn't go back to the instructor to
            // re-track - see docs/instructor-attendance-block-proposal.md, "The
            // triggering session".
            if ($block->triggered_by_session_id !== null) {
                $this->autoFillTriggeringSession->handle($block->triggered_by_session_id, $actor);
            }
        });
    }
}
