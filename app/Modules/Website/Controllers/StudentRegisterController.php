<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
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
        return Inertia::render('frontend/student-register/Index', [
            'categories' => $this->categories(),
            'courses' => $this->courses(),
            'terms' => Term::query()->select('id', 'term_name')->orderBy('term_name')->get(),
            'times' => Time::query()->select('id', 'time_name')->orderBy('time_name')->get(),
        ]);
    }

    public function store(StudentRegisterRequest $request, RegisterStudentForSchedule $register): RedirectResponse
    {
        $register->handle($request->validated());

        return redirect()
            ->route('frontend.student-register.create')
            ->with('success', 'Registration received. We added you to the matching class schedule.');
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
            ])
            ->filter(fn (array $course): bool => $course['category_id'] !== null)
            ->values()
            ->all();
    }
}
