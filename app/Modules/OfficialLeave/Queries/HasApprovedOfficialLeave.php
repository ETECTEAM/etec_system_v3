<?php

namespace App\Modules\OfficialLeave\Queries;

use App\Models\OfficialLeave;
use Illuminate\Support\Carbon;

/**
 * True when the student has an approved official leave covering $date. Official
 * leave outranks everything at attendance time — instructors can't mark these
 * days absent, and auto-record writes them as 'on_leave'.
 */
class HasApprovedOfficialLeave
{
    public function handle(int $studentId, Carbon|string|null $date = null): bool
    {
        $date = Carbon::parse($date ?? now())->toDateString();

        return OfficialLeave::query()
            ->where('student_id', $studentId)
            ->where('status', OfficialLeave::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }
}
