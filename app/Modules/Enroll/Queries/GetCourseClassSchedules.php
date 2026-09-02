<?php

namespace App\Modules\Enroll\Queries;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\CourseTrack;
use App\Models\Schedule;
use App\Models\StudyClass;
use App\Models\Time;
use Illuminate\Support\Collection;

class GetCourseClassSchedules
{
    /**
     * Fallback set used only for courses whose track has no class_type_id
     * mapping - keeps their Enroll Config working exactly as it did before the
     * mapping existed. A mapped track shows ONLY its own Class Type instead.
     */
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
     * query - a handful of queries total, not ~2N (the enroll-config page
     * renders dozens of courses).
     *
     * Each course sees only the schedules of the Class Type its track is
     * mapped to (course_tracks.class_type_id); an unmapped track falls back to
     * AVAILABLE_CLASS_TYPES so nothing breaks for courses configured before
     * the mapping existed.
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

        // course id -> mapped class_type_id (via its track), null when unmapped.
        $mappedTypeByCourse = Course::query()
            ->whereIn('courses.id', $courseIds)
            ->leftJoin('course_tracks', 'course_tracks.id', '=', 'courses.course_track_id')
            ->pluck('course_tracks.class_type_id', 'courses.id')
            ->map(fn ($id) => $id !== null ? (int) $id : null);

        $defaultTypeIds = $this->defaultClassTypeIds();

        // Every Class Type any of these courses could show - one Schedule query.
        $neededTypeIds = $defaultTypeIds
            ->merge($mappedTypeByCourse->filter()->values())
            ->unique()
            ->values();

        // Open schedule-scoped rows keyed "scheduleId:timeId", grouped by course -
        // carries max_classes so the page can show / edit each slot's class limit.
        $openConfigsByCourse = CourseEnrollConfig::query()
            ->whereIn('course_id', $courseIds)
            ->whereNotNull('schedule_id')
            ->get(['course_id', 'schedule_id', 'time_id', 'max_classes'])
            ->groupBy('course_id')
            ->map(fn (Collection $rows) => $rows->keyBy(fn (CourseEnrollConfig $config) => "{$config->schedule_id}:{$config->time_id}"));

        $schedules = Schedule::query()
            ->whereIn('class_type_id', $neededTypeIds->all())
            ->with([
                'classType:class_type_id,type_name',
                'term:id,term_name',
                'times' => fn ($query) => $query->orderBy('id'),
            ])
            ->get();

        return $courseIds
            ->mapWithKeys(function (int $courseId) use ($schedules, $openConfigsByCourse, $mappedTypeByCourse, $defaultTypeIds) {
                $mappedTypeId = $mappedTypeByCourse->get($courseId);

                $courseSchedules = $mappedTypeId !== null
                    ? $schedules->where('class_type_id', $mappedTypeId)
                    : $schedules->whereIn('class_type_id', $defaultTypeIds->all());

                return [
                    $courseId => $this->build(
                        $courseSchedules->values(),
                        $openConfigsByCourse->get($courseId) ?? collect(),
                    ),
                ];
            })
            ->all();
    }

    /**
     * The Class Type ids a course may be configured against on the Enroll
     * Config page: its track's mapped Class Type when set, otherwise the
     * default fallback set. Used by the read query above and by the write
     * guards in CourseController so both agree on what is valid.
     *
     * @return Collection<int, int>
     */
    public function classTypeIdsForCourse(Course $course): Collection
    {
        $mapped = $course->relationLoaded('track')
            ? $course->track?->class_type_id
            : CourseTrack::query()->whereKey($course->course_track_id)->value('class_type_id');

        if ($mapped !== null) {
            return collect([(int) $mapped]);
        }

        return $this->defaultClassTypeIds();
    }

    /**
     * class_type ids for AVAILABLE_CLASS_TYPES, in that order.
     *
     * @return Collection<int, int>
     */
    private function defaultClassTypeIds(): Collection
    {
        return ClassType::query()
            ->whereIn('type_name', self::AVAILABLE_CLASS_TYPES)
            ->pluck('class_type_id', 'type_name')
            ->sortBy(fn ($id, $name) => array_search($name, self::AVAILABLE_CLASS_TYPES, true))
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * @param  Collection<int, Schedule>  $schedules  the schedules this one course may show
     * @param  Collection<string, CourseEnrollConfig>  $openConfigs  open rows keyed "scheduleId:timeId" for one course
     * @return array<int, array<string, mixed>>
     */
    private function build(Collection $schedules, Collection $openConfigs): array
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
