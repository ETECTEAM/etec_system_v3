<?php

namespace App\Modules\Enroll\Queries;

use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GetCourseEnrollConfigs
{
    public function handle(Request $request): LengthAwarePaginator
    {
        $search = trim($request->string('search')->toString());

        $courses = Course::query()
            ->select(['id', 'title'])
            ->with('enrollConfig:id,course_id,status,start_date,price,document_price')
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
            ->orderBy('title')
            ->paginate($this->resolvePerPage($request))
            ->withQueryString();

        return $courses->through(fn (Course $course) => $this->present($course));
    }

    private function present(Course $course): array
    {
        return [
            'id' => $course->id,
            'title' => $course->title,
            'enroll_status' => $course->enrollConfig?->status ?? 'open',
            'start_date' => optional($course->enrollConfig?->start_date)->format('Y-m-d'),
            'price' => (float) ($course->enrollConfig?->price ?? 0),
            'document_price' => (float) ($course->enrollConfig?->document_price ?? 5),
        ];
    }

    private function resolvePerPage(Request $request): int
    {
        if ($request->input('per_page') === 'all') {
            return max(1, Course::query()->count());
        }

        return max(1, min(1000, (int) $request->integer('per_page', 10)));
    }
}
