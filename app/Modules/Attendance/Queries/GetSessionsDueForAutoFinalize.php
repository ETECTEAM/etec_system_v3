<?php

namespace App\Modules\Attendance\Queries;

use App\Models\ClassSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GetSessionsDueForAutoFinalize
{
    /**
     * Sessions auto-recorded by the system whose correction window has expired.
     * The finalizer only touches the rows that are still sitting in the provisional
     * "pending" state, so a manually corrected session is left alone.
     */
    public function handle(Carbon $now, int $overrideHours): Collection
    {
        return ClassSession::query()
            ->where('status', ClassSession::STATUS_AUTO_RECORDED)
            ->whereNotNull('recorded_at')
            ->where('recorded_at', '<=', $now->copy()->subHours($overrideHours))
            ->pluck('id');
    }
}
