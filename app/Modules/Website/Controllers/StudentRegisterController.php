<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\Schedule;
use App\Models\Term;
use App\Models\Time;
use App\Modules\Enroll\Queries\GetCourseClassSchedules;
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
                'class_types' => $this->classTypesForTerm($term->id),
            ])
            ->all();
    }

    private function classTypesForTerm(int $termId): array
    {
        return Schedule::query()
            ->where('term_id', $termId)
            ->whereHas('classType', fn ($query) => $query->whereIn('type_name', GetCourseClassSchedules::AVAILABLE_CLASS_TYPES))
            ->with(['classType:class_type_id,type_name', 'times:id'])
            ->get()
            ->map(fn (Schedule $schedule): array => [
                'class_type_id' => $schedule->class_type_id,
                'class_type_name' => $schedule->classType->type_name,
                'time_ids' => $schedule->times->pluck('id')->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function courses(): array
    {
        return Course::query()
            ->with('track.subCategory.category:id,name', 'enrollConfigs.schedule:id,class_type_id')
            ->where('status', 'active')
            ->select('id', 'course_track_id', 'title', 'level', 'enroll_order')
            // Admin-set display order (Enroll Config page) - 1 shows first;
            // unordered courses fall back below the ordered ones, by title.
            ->orderByRaw('enroll_order IS NULL, enroll_order asc')
            ->orderBy('title')
            ->get()
            ->map(fn (Course $course): array => [
                'id' => $course->id,
                'title' => $course->title,
                'level' => $course->level,
                'category_id' => $course->track?->subCategory?->category?->id,
                'category_name' => $course->track?->subCategory?->category?->name,
                'sub_category_id' => $course->track?->subCategory?->id,
                'sub_category_name' => $course->track?->subCategory?->name,
                // Empty means the course has nothing toggled open yet - it
                // simply won't show any bookable slot, matching what the
                // Class Schedules picker displays (no hidden fallback).
                'class_types' => $this->openClassTypesForCourse($course),
            ])
            ->filter(fn (array $course): bool => $course['category_id'] !== null)
            ->values()
            ->all();
    }

    // The default config's status (the course-wide Open/Closed toggle on the
    // Enroll Config page) is a master switch: closed hides the course from
    // public registration outright, regardless of which individual class
    // type/time slots are toggled open, so pausing a course doesn't require
    // touching every badge.
    private function openClassTypesForCourse(Course $course): array
    {
        $default = $course->enrollConfigs->first(
            fn (CourseEnrollConfig $config) => $config->schedule_id === null && $config->time_id === null
        );

        if ($default !== null && $default->status !== 'open') {
            return [];
        }

        return $course->enrollConfigs
            ->filter(fn (CourseEnrollConfig $config) => $config->schedule_id !== null && $config->status === 'open')
            ->groupBy(fn (CourseEnrollConfig $config) => $config->schedule->class_type_id)
            ->map(fn ($configs, $classTypeId): array => [
                'class_type_id' => (int) $classTypeId,
                'time_ids' => $configs->pluck('time_id')->unique()->values()->all(),
            ])
            ->values()
            ->all();
    }
}
