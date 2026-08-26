<?php

namespace App\Modules\Enroll\Queries;

use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\Schedule;

class GetCourseClassSchedules
{
    public const AVAILABLE_CLASS_TYPES = ['Physical Class', 'Scholarship Class', 'Online Class'];

    // Class Type -> Term -> Time from Schedule Management, each time marked
    // open/closed for this course. is_enabled is derived, not stored.
    public function handle(Course $course): array
    {
        $openKeys = CourseEnrollConfig::where('course_id', $course->id)
            ->whereNotNull('schedule_id')
            ->get(['schedule_id', 'time_id'])
            ->map(fn (CourseEnrollConfig $config) => "{$config->schedule_id}:{$config->time_id}")
            ->flip();

        $schedules = Schedule::query()
            ->whereHas('classType', fn ($query) => $query->whereIn('type_name', self::AVAILABLE_CLASS_TYPES))
            ->with([
                'classType:class_type_id,type_name',
                'term:id,term_name',
                'times' => fn ($query) => $query->orderBy('id'),
            ])
            ->get();

        return $schedules
            ->groupBy(fn (Schedule $schedule) => $schedule->classType->type_name)
            ->sortBy(fn ($group, $typeName) => array_search($typeName, self::AVAILABLE_CLASS_TYPES))
            ->map(function ($group) use ($openKeys) {
                $terms = $group
                    ->sortBy(fn (Schedule $schedule) => $schedule->term?->term_name ?? '')
                    ->map(fn (Schedule $schedule) => [
                        'schedule_id' => $schedule->id,
                        'term_id' => $schedule->term_id,
                        'term_name' => $schedule->term?->term_name,
                        'times' => $schedule->times->map(fn ($time) => [
                            'time_id' => $time->id,
                            'time_name' => $time->time_name,
                            'is_open' => $openKeys->has("{$schedule->id}:{$time->id}"),
                        ])->values()->all(),
                    ])
                    ->values()
                    ->all();

                $first = $group->first();

                return [
                    'class_type_id' => $first->class_type_id,
                    'class_type_name' => $first->classType->type_name,
                    'terms' => $terms,
                    'is_enabled' => collect($terms)->contains(
                        fn ($term) => collect($term['times'])->contains('is_open', true)
                    ),
                ];
            })
            ->values()
            ->all();
    }
}
