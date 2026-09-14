<?php

namespace App\Modules\Attendance\Actions;

use App\Models\ClassSession;
use App\Models\InstructorAttendanceBlock;
use Illuminate\Support\Carbon;

/**
 * Case 1 of docs/instructor-attendance-block-proposal.md: an instructor who lets a session go
 * to pre_attendance (0 students tracked by grace time) is blocked from tracking attendance on
 * EVERY class they teach, not just this one, until an admin approves their unblock request.
 */
class BlockInstructorForMissedAttendance
{
    public function handle(ClassSession $session): void
    {
        if (! $session->instructor_id) {
            return;
        }

        $now = Carbon::now('Asia/Phnom_Penh');
        $courseTitle = $session->studyClass?->course?->title ?? 'a class';

        $existing = InstructorAttendanceBlock::query()
            ->where('instructor_id', $session->instructor_id)
            ->whereIn('status', InstructorAttendanceBlock::BLOCKING_STATUSES)
            ->latest('blocked_at')
            ->first();

        $attributes = [
            'triggered_by_session_id' => $session->id,
            'reason' => sprintf('Attendance for %s was not recorded on %s.', $courseTitle, $session->session_date->toDateString()),
            'status' => InstructorAttendanceBlock::STATUS_ACTIVE,
            'blocked_at' => $now,
            'unblock_requested_at' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'note' => null,
        ];

        if ($existing) {
            $existing->update($attributes);

            return;
        }

        InstructorAttendanceBlock::query()->create([
            'instructor_id' => $session->instructor_id,
            ...$attributes,
        ]);
    }
}
