<?php

namespace App\Modules\Enroll\Queries;

use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\Time;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class GetCourseEnrollConfigs
{
    /**
     * Courses grouped Category -> SubCategory -> CourseTrack, for the
     * enroll-config page's hierarchical table. Each course carries its own
     * set of enrollment schedules (one per time slot) plus the shared list of
     * time slots the admin can pick from. Unpaginated - grouping and
     * per-page course pagination don't compose (a track's courses could
     * split across pages), and the course count here is small enough
     * (dozens, not thousands) that loading everything at once is fine.
     */
    public function handle(Request $request): array
    {
        $search = trim($request->string('search')->toString());

        $courses = Course::query()
            ->select(['id', 'title', 'course_track_id'])
            ->with([
                'enrollConfigs:id,course_id,time_id,status,start_date,unit_price,course_price,selected_price_type,document_price',
                'enrollConfigs.time:id,time_name',
                'track:id,sub_category_id,name',
                'track.subCategory:id,category_id,name',
                'track.subCategory.category:id,name',
            ])
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
            ->orderBy('title')
            ->get();

        return [
            'categories' => $this->group($courses),
            'times' => Time::query()->orderBy('id')->get(['id', 'time_name'])
                ->map(fn (Time $time) => [
                    'value' => (string) $time->id,
                    'label' => $time->time_name,
                ])
                ->values()
                ->all(),
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
                        'courses' => $uncategorized->sortBy('title')->values()->map(fn (Course $course) => $this->present($course))->all(),
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
            'courses' => $trackCourses->sortBy('title')->values()->map(fn (Course $course) => $this->present($course))->all(),
        ];
    }

    private function present(Course $course): array
    {
        $configs = $course->enrollConfigs
            ->sortBy(fn (CourseEnrollConfig $config) => $config->time?->time_name ?? '')
            ->values()
            ->map(fn (CourseEnrollConfig $config) => $this->presentConfig($config))
            ->all();

        // A course with no saved config still renders one default row so the
        // admin can open/price it from the page (pre-existing behavior kept).
        if ($configs === []) {
            $configs[] = $this->presentConfig(null);
        }

        return [
            'id' => $course->id,
            'title' => $course->title,
            'configs' => $configs,
        ];
    }

    private function presentConfig(?CourseEnrollConfig $config): array
    {
        return [
            'id' => $config?->id ?? null,
            'time_id' => $config?->time_id ?? null,
            'time_name' => $config?->time?->time_name ?? null,
            'enroll_status' => $config?->status ?? 'open',
            'start_date' => optional($config?->start_date)->format('Y-m-d'),
            'unit_price' => (float) ($config?->unit_price ?? 0),
            'course_price' => (float) ($config?->course_price ?? 0),
            'selected_price_type' => $config?->selected_price_type ?? CourseEnrollConfig::PRICE_TYPE_COURSE,
            'resolved_price' => $config?->resolvedPrice() ?? 0,
            'document_price' => (float) ($config?->document_price ?? 5),
        ];
    }
}
