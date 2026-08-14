<?php

namespace App\Modules\Attendance\Queries;

use App\Models\ClassSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GetSessionsDueForAutoRecord
{
    /**
     * Sessions past grace and still not submitted — but not past their own end time,
     * which the caller marks 'missed' instead of auto-recording.
     */
    public function handle(Carbon $now, int $graceMinutes): Collection
    {
        return ClassSession::query()
            ->where('status', ClassSession::STATUS_PENDING)
            ->whereDate('session_date', $now->toDateString())
            ->where('scheduled_start', '<=', $now->copy()->subMinutes($graceMinutes))
            ->where('scheduled_end', '>', $now)
            ->pluck('id');
    }

    public function pastEnd(Carbon $now): Collection
    {
        return ClassSession::query()
            ->where('status', ClassSession::STATUS_PENDING)
            ->whereDate('session_date', $now->toDateString())
            ->where('scheduled_end', '<=', $now)
            ->pluck('id');
    }
}
