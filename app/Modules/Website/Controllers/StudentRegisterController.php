<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\SubCategory;
use App\Models\Term;
use App\Models\Time;
use App\Modules\Website\Actions\RegisterStudentForSchedule;
use App\Modules\Website\Requests\StudentRegisterRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StudentRegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('frontend/student-register/StudentRegister', [
            'categories' => $this->categories(),
            'subCategories' => $this->subCategories(),
            'courses' => $this->courses(),
            'terms' => $this->terms(),
            'times' => Time::query()->select('id', 'time_name')->orderBy('time_name')->get(),
        ]);
    }

    public function store(StudentRegisterRequest $request, RegisterStudentForSchedule $register): RedirectResponse
    {
        $enrollment = $register->handle($request->validated());

        $message = match (true) {
            $enrollment === null => 'Registration received. No class is available for that time right now — our staff will confirm your class shortly.',
            $enrollment->wasRecentlyCreated => 'Registration received. We added you to the matching class schedule.',
            default => "You're already registered for that class.",
        };

        return redirect()
            ->route('frontend.student-register.create')
            ->with('success', $message);
    }

    private function categories(): array
    {
        return Category::query()
            ->where('status', 'active')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->all();
    }

    private function terms(): array
    {
        // Public registration only ever runs these two term patterns in
        // practice; the other terms in the table are for other intake paths.
        return Term::query()
            ->whereIn('term_name', ['Mon & Thu', 'Sat & Sun'])
            ->select('id', 'term_name')
            ->orderBy('term_name')
            ->get()
            ->map(fn (Term $term): array => [
                'id' => $term->id,
                'term_name' => $term->term_name,
                'time_ids' => $this->timeIdsForTerm($term->id),
            ])
            ->all();
    }

    // Scholarship Class runs its own separate time slots and isn't offered
    // through this public self-registration form, so it's excluded here.
    private function timeIdsForTerm(int $termId): array
    {
        return Schedule::query()
            ->where('term_id', $termId)
            ->whereHas('classType', fn ($query) => $query->where('type_name', '!=', 'Scholarship Class'))
            ->with('times:id')
            ->get()
            ->pluck('times')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }

    private function subCategories(): array
    {
        return SubCategory::query()
            ->where('status', 'active')
            ->select('id', 'category_id', 'name')
            ->orderBy('name')
            ->get()
            ->all();
    }

    private function courses(): array
    {
        return Course::query()
            ->with('track.subCategory.category:id,name')
            ->where('status', 'active')
            ->select('id', 'course_track_id', 'title')
            ->orderBy('title')
            ->get()
            ->map(fn (Course $course): array => [
                'id' => $course->id,
                'title' => $course->title,
                'category_id' => $course->track?->subCategory?->category?->id,
                'sub_category_id' => $course->track?->subCategory?->id,
            ])
            ->filter(fn (array $course): bool => $course['category_id'] !== null)
            ->values()
            ->all();
    }
}
