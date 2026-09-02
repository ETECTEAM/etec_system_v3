<?php

namespace App\Modules\Enroll\Queries;

use App\Models\Course;
use App\Models\CourseEnrollConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class GetCourseEnrollConfigs
{
    public function __construct(private readonly GetCourseClassSchedules $classSchedules) {}

    /**
     * Class Type -> Term -> Time availability per course id, prefetched once in
     * handle() so present() doesn't hit the DB per course. Request-scoped: this
     * query object is resolved fresh for each request.
     *
     * @var array<int, array<int, array<string, mixed>>>
     */
    private array $schedulesByCourse = [];

    /**
     * Courses grouped Category -> SubCategory -> CourseTrack, for the
     * enroll-config page's hierarchical table. Each course carries its own
     * default pricing config plus its Class Schedules availability.
     * Unpaginated - grouping and per-page course pagination don't compose (a
     * track's courses could split across pages), and the course count here
     * is small enough (dozens, not thousands) that loading everything at
     * once is fine.
     */
    public function handle(Request $request): array
    {
        $search = trim($request->string('search')->toString());

        $courses = Course::query()
            ->select(['id', 'title', 'course_track_id', 'enroll_order'])
            ->with([
                'enrollConfig',
                'track:id,sub_category_id,class_type_id,name',
                'track.classType:class_type_id,type_name',
                'track.subCategory:id,category_id,name',
                'track.subCategory.category:id,name',
            ])
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
            ->orderBy('title')
            ->get();

        // One pair of queries for every course's class-schedule availability,
        // instead of two per course inside present().
        $this->schedulesByCourse = $this->classSchedules->handleMany($courses->pluck('id'));

        return [
            'categories' => $this->group($courses),
            'filters' => ['search' => $search],
        ];
    }

    /**
     * @param  Collection<int, Course>  $courses
     * @return array<int, array<string, mixed>>
     */
    private function group(Collection $courses): array
    {
        $categorized = $courses->filter(fn (Course $course) => $course->track?->subCategory?->category !== null);

        $groups = $categorized
            ->groupBy(fn (Course $course) => $course->track->subCategory->category_id)
            ->map(fn (Collection $categoryCourses) => $this->presentCategory($categoryCourses))
            ->sortBy('name')
            ->values();

        // course_track_id is nullable (see courses migration), so a course can
        // exist with no track/sub-category/category. Rather than silently drop
        // it from the page - the admin would lose the ability to price it -
        // bucket every such course under one "Uncategorized" group.
        $uncategorized = $courses->diff($categorized);

        if ($uncategorized->isNotEmpty()) {
            $groups->push([
                'id' => null,
                'name' => 'Uncategorized',
                'subCategories' => [[
                    'id' => null,
                    'name' => 'Uncategorized',
                    'tracks' => [[
                        'id' => null,
                        'name' => 'Uncategorized',
                        'courses' => $uncategorized
                            ->sortBy(fn (Course $course) => [$course->enroll_order ?? PHP_INT_MAX, $course->title])
                            ->values()
                            ->map(fn (Course $course) => $this->present($course))
                            ->all(),
                    ]],
                ]],
            ]);
        }

        return $groups->values()->all();
    }

    /**
     * @param  Collection<int, Course>  $categoryCourses  every course under one category
     */
    private function presentCategory(Collection $categoryCourses): array
    {
        $category = $categoryCourses->first()->track->subCategory->category;

        $subCategories = $categoryCourses
            ->groupBy(fn (Course $course) => $course->track->sub_category_id)
            ->map(fn (Collection $subCategoryCourses) => $this->presentSubCategory($subCategoryCourses))
            ->sortBy('name')
            ->values();

        return [
            'id' => $category->id,
            'name' => $category->name,
            'subCategories' => $subCategories->all(),
        ];
    }

    /**
     * @param  Collection<int, Course>  $subCategoryCourses  every course under one sub-category
     */
    private function presentSubCategory(Collection $subCategoryCourses): array
    {
        $subCategory = $subCategoryCourses->first()->track->subCategory;

        $tracks = $subCategoryCourses
            ->groupBy('course_track_id')
            ->map(fn (Collection $trackCourses) => $this->presentTrack($trackCourses))
            ->sortBy('name')
            ->values();

        return [
            'id' => $subCategory->id,
            'name' => $subCategory->name,
            'tracks' => $tracks->all(),
        ];
    }

    /**
     * @param  Collection<int, Course>  $trackCourses  every course under one track
     */
    private function presentTrack(Collection $trackCourses): array
    {
        $track = $trackCourses->first()->track;

        return [
            'id' => $track->id,
            'name' => $track->name,
            // Admin-set enroll order first (nulls last), then title - mirrors
            // the ordering the public student-register list uses.
            'courses' => $trackCourses
                ->sortBy(fn (Course $course) => [$course->enroll_order ?? PHP_INT_MAX, $course->title])
                ->values()
                ->map(fn (Course $course) => $this->present($course))
                ->all(),
        ];
    }

    private function present(Course $course): array
    {
        $classType = $course->track?->classType;

        return [
            'id' => $course->id,
            'title' => $course->title,
            'enroll_order' => $course->enroll_order,
            'config' => $this->presentConfig($course->enrollConfig),
            // Resolved via course_tracks.class_type_id. mapped = false means the
            // course_schedules below are the default fallback set, not a real
            // Course -> Class Type mapping - the UI flags this for the admin.
            'class_type' => [
                'id' => $classType?->class_type_id,
                'name' => $classType?->type_name,
                'mapped' => $classType !== null,
            ],
            'class_schedules' => $this->schedulesByCourse[$course->id] ?? [],
        ];
    }

    private function presentConfig(?CourseEnrollConfig $config): array
    {
        return [
            'id' => $config?->id ?? null,
            'enroll_status' => $config?->status ?? 'open',
            'start_date' => optional($config?->start_date)->format('Y-m-d'),
            'unit_price' => (float) ($config?->unit_price ?? 0),
            'course_price' => (float) ($config?->course_price ?? 0),
            'resolved_price' => $config?->resolvedPrice() ?? 0,
            'document_price' => (float) ($config?->document_price ?? 5),
        ];
    }
}
