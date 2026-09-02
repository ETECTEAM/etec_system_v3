<?php

namespace App\Modules\Enroll\Queries;

use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\Schedule;
use App\Models\StudyClass;
use App\Models\Time;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class GetCourseClassSchedules
{
    public const AVAILABLE_CLASS_TYPES = ['Physical Class', 'Scholarship Class', 'Online Class'];

    /**
     * Class Type -> Term -> Time from Schedule Management, each time marked
     * open/closed for this one course. is_enabled is derived, not stored.
     *
     * @return array<int, array<string, mixed>>
     */
    public function handle(Course $course): array
    {
        return $this->handleMany([$course->id])[$course->id] ?? [];
    }

    /**
     * Same shape as handle(), but for many courses at once, keyed by course id.
     * Fetches the schedule tree once and every course's open slots in a single
     * query - two queries total instead of two per course (the enroll-config
     * page renders dozens of courses, so the per-course version was ~2N).
     *
     * @param  iterable<int>  $courseIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function handleMany(iterable $courseIds): array
    {
        $courseIds = collect($courseIds)->map(fn ($id) => (int) $id)->unique()->values();

        if ($courseIds->isEmpty()) {
            return [];
        }

        // Open schedule-scoped rows keyed "scheduleId:timeId", grouped by course -
        // carries max_classes so the page can show / edit each slot's class limit.
        $openConfigsByCourse = CourseEnrollConfig::query()
            ->whereIn('course_id', $courseIds)
            ->whereNotNull('schedule_id')
            ->get(['course_id', 'schedule_id', 'time_id', 'max_classes'])
            ->groupBy('course_id')
            ->map(fn (Collection $rows) => $rows->keyBy(fn (CourseEnrollConfig $config) => "{$config->schedule_id}:{$config->time_id}"));

        $schedules = Schedule::query()
            ->whereHas('classType', fn ($query) => $query->whereIn('type_name', self::AVAILABLE_CLASS_TYPES))
            ->with([
                'classType:class_type_id,type_name',
                'term:id,term_name',
                'times' => fn ($query) => $query->orderBy('id'),
            ])
            ->get();

        return $courseIds
            ->mapWithKeys(fn (int $courseId) => [
                $courseId => $this->build($schedules, $openConfigsByCourse->get($courseId) ?? collect()),
            ])
            ->all();
    }

    /**
     * @param  EloquentCollection<int, Schedule>  $schedules  the full schedule tree, fetched once
     * @param  Collection<string, CourseEnrollConfig>  $openConfigs  open rows keyed "scheduleId:timeId" for one course
     * @return array<int, array<string, mixed>>
     */
    private function build(EloquentCollection $schedules, Collection $openConfigs): array
    {
        return $schedules
            ->groupBy(fn (Schedule $schedule) => $schedule->classType->type_name)
            ->sortBy(fn ($group, $typeName) => array_search($typeName, self::AVAILABLE_CLASS_TYPES))
            ->map(function ($group) use ($openConfigs) {
                $terms = $group
                    ->sortBy(fn (Schedule $schedule) => $schedule->term?->term_name ?? '')
                    ->map(fn (Schedule $schedule) => [
                        'schedule_id' => $schedule->id,
                        'term_id' => $schedule->term_id,
                        'term_name' => $schedule->term?->term_name,
                        // Chronological by real start time, not Time.id / pivot
                        // order - a slot like "02:00 pm - 3:15 pm" added later
                        // must still sort between 12:30 pm and 03:30 pm. Mirrors
                        // GetClassFormOptions::scheduleGroups().
                        'times' => $schedule->times
                            ->sortBy(fn (Time $time) => StudyClass::parseTimeRange($time->time_name)['start'] ?? '99:99')
                            ->map(function (Time $time) use ($schedule, $openConfigs) {
                                $config = $openConfigs->get("{$schedule->id}:{$time->id}");

                                return [
                                    'time_id' => $time->id,
                                    'time_name' => $time->time_name,
                                    'is_open' => $config !== null,
                                    'max_classes' => $config?->max_classes,
                                ];
                            })->values()->all(),
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
