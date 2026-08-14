<?php

namespace App\Modules\Attendance\Queries;

use App\Models\StudyClass;
use App\Models\Time;
use Illuminate\Support\Carbon;

/**
 * Shortest duration among every configured time slot — the grace period can never be
 * allowed to exceed this, or a short class could end before the grace period even elapses,
 * meaning it could never be auto-recorded (and would eventually just be marked missed).
 */
class GetShortestClassDurationMinutes
{
    public function handle(): ?int
    {
        $shortest = null;

        foreach (Time::query()->pluck('time_name') as $timeName) {
            $range = StudyClass::parseTimeRange($timeName);

            if (! $range['start'] || ! $range['end']) {
                continue;
            }

            $start = Carbon::createFromFormat('H:i', $range['start']);
            $end = Carbon::createFromFormat('H:i', $range['end']);
            $minutes = $start->diffInMinutes($end);

            if ($minutes <= 0) {
                continue;
            }

            $shortest = $shortest === null ? $minutes : min($shortest, $minutes);
        }

        return $shortest;
    }
}
