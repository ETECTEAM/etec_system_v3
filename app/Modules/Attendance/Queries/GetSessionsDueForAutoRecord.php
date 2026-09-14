<?php

namespace App\Modules\Attendance\Queries;

use App\Models\ClassSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GetSessionsDueForAutoRecord
{
    /**
     * Sessions past grace that still need student-by-student finalization.
     */
    public function handle(Carbon $now, int $graceMinutes): Collection
    {
        return ClassSession::query()
            ->whereIn('status', [
                ClassSession::STATUS_PENDING,
                ClassSession::STATUS_PRE_ATTENDANCE,
                ClassSession::STATUS_PARTIAL,
            ])
            ->whereDate('session_date', $now->toDateString())
            ->where('scheduled_start', '<=', $now->copy()->subMinutes($graceMinutes))
            ->pluck('id');
    }

    public function pastEnd(Carbon $now): Collection
    {
        return ClassSession::query()
            ->whereIn('status', [
                ClassSession::STATUS_PENDING,
                ClassSession::STATUS_PRE_ATTENDANCE,
                ClassSession::STATUS_PARTIAL,
            ])
            ->whereDate('session_date', $now->toDateString())
            ->where('scheduled_end', '<=', $now)
            ->pluck('id');
    }
}
