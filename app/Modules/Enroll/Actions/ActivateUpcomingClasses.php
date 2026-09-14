<?php

namespace App\Modules\Enroll\Actions;

use App\Models\CourseEnrollConfig;
use App\Models\StudyClass;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ActivateUpcomingClasses
{
    /**
     * Activate upcoming classes whose start date has arrived.
     *
     * Lifecycle is date-based only — enrollment capacity is independent.
     *
     * @return Collection<int, StudyClass> the classes that were activated
     */
    public function handle(Carbon $date = null): Collection
    {
        $date = $date ?? Carbon::now('Asia/Phnom_Penh');
        $today = $date->toDateString();

        $upcoming = StudyClass::query()
            ->where('status', 'upcoming')
            ->get();

        $activated = collect();

        foreach ($upcoming as $class) {
            if ($this->shouldActivate($class, $today)) {
                $class->update(['status' => 'active']);
                $activated->push($class);
            }
        }

        return $activated;
    }

    private function shouldActivate(StudyClass $class, string $today): bool
    {
        // Class's own start_date has arrived
        if ($class->start_date && $class->start_date->toDateString() <= $today) {
            return true;
        }

        // Matching course_enroll_config start_date has arrived
        if ($this->enrollConfigStartDateArrived($class, $today)) {
            return true;
        }

        return false;
    }

    private function enrollConfigStartDateArrived(StudyClass $class, string $today): bool
    {
        return CourseEnrollConfig::query()
            ->where('course_id', $class->course_id)
            ->where('time_id', $class->time_id)
            ->whereNotNull('start_date')
            ->where('start_date', '<=', $today)
            ->exists();
    }
}
