<?php

namespace App\Modules\Attendance\Queries;

use App\Models\ClassSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GetSessionsDueForAutoFinalize
{
    /**
     * Sessions whose override window has expired and still need per-student finalization.
     */
    public function handle(Carbon $now, int $overrideHours): Collection
    {
        return ClassSession::query()
            ->whereIn('status', [
                ClassSession::STATUS_PRE_ATTENDANCE,
                ClassSession::STATUS_PARTIAL,
                ClassSession::STATUS_AUTO_RECORDED,
            ])
            ->whereNotNull('recorded_at')
            ->where('recorded_at', '<=', $now->copy()->subHours($overrideHours))
            ->pluck('id');
    }
}
