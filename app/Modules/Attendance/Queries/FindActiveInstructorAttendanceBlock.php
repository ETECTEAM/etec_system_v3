<?php

namespace App\Modules\Attendance\Queries;

use App\Models\InstructorAttendanceBlock;

class FindActiveInstructorAttendanceBlock
{
    public function handle(int $instructorId): ?InstructorAttendanceBlock
    {
        return InstructorAttendanceBlock::query()
            ->where('instructor_id', $instructorId)
            ->whereIn('status', InstructorAttendanceBlock::BLOCKING_STATUSES)
            ->latest('blocked_at')
            ->first();
    }
}
