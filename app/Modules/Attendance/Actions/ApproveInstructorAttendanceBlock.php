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
        private readonly BackfillAllMissedSessionsFromLastWeek $backfillAllMissedSessions,
    ) {}

    /**
     * @param  string  $reviewedReason  InstructorAttendanceBlock::REASON_GENERAL (plain
     *                                  Approve, backfills only the triggering session) or
     *                                  REASON_PERMISSION (Approve After Permission, backfills
     *                                  every stuck session across all the instructor's classes).
     */
    public function handle(InstructorAttendanceBlock $block, User $actor, string $reviewedReason = InstructorAttendanceBlock::REASON_GENERAL): void
    {
        if (! in_array($block->status, InstructorAttendanceBlock::BLOCKING_STATUSES, true)) {
            throw ValidationException::withMessages([
                'block' => 'This block is no longer pending.',
            ]);
        }

        $isPermission = $reviewedReason === InstructorAttendanceBlock::REASON_PERMISSION;

        if (! in_array($reviewedReason, InstructorAttendanceBlock::REVIEWED_REASONS, true)) {
            throw ValidationException::withMessages([
                'block' => 'The approval reason is invalid.',
            ]);
        }

        if ($isPermission) {
            // Approve After Permission: the wide sweep chunk-commits itself (see
            // BackfillAllMissedSessionsFromLastWeek), so the block status flips in its
            // own short transaction first — wrapping the sweep here would collapse the
            // whole backlog into one giant transaction with every session locked.
            DB::transaction(function () use ($block, $actor): void {
                $block->update([
                    'status' => InstructorAttendanceBlock::STATUS_APPROVED_UNBLOCK,
                    'reviewed_by' => $actor->id,
                    'reviewed_at' => now(),
                    'reviewed_reason_type' => InstructorAttendanceBlock::REASON_PERMISSION,
                ]);
            });

            $this->backfillAllMissedSessions->handle($block, $actor);

            return;
        }

        // Plain Approve — unchanged from before: one transaction flips the block and
        // backfills only the triggering session. The class that caused the block
        // doesn't go back to the instructor to re-track (see
        // docs/instructor-attendance-block-proposal.md, "The triggering session").
        DB::transaction(function () use ($block, $actor): void {
            $block->update([
                'status' => InstructorAttendanceBlock::STATUS_APPROVED_UNBLOCK,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'reviewed_reason_type' => InstructorAttendanceBlock::REASON_GENERAL,
            ]);

            if ($block->triggered_by_session_id !== null) {
                $this->autoFillTriggeringSession->handle($block->triggered_by_session_id, $actor);
            }
        });
    }
}