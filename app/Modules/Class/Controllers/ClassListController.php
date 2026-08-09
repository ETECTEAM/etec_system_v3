<?php

namespace App\Modules\Class\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassType;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Room;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use App\Modules\Enroll\Queries\GetClassFormOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClassListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * Backed by study_classes (StudyClass), the single source of truth for
     * classes across the app. class_list was a duplicate table and has been
     * dropped.
     */
    public function index(Request $request)
    {
        $classLists = StudyClass::with([
            'teacher', 'course', 'lesson', 'term', 'time', 'room.floor.building', 'classType',
        ])->withCount([
            'enrollments as current_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $classLists->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('classType', fn ($q) => $q->where('type_name', 'like', "%{$search}%"))
                    ->orWhereHas('teacher', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('term', fn ($q) => $q->where('term_name', 'like', "%{$search}%"))
                    ->orWhereHas('time', fn ($q) => $q->where('time_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $classLists->where('status', $request->status);
        }

        if ($request->filled('class_type') && $request->class_type !== 'all') {
            $classLists->whereHas('classType', fn ($query) => $query->where('type_name', $request->class_type));
        }

        if ($request->filled('term') && $request->term !== 'all') {
            $classLists->whereHas('term', fn ($query) => $query->where('term_name', $request->term));
        }

        if ($request->filled('time') && $request->time !== 'all') {
            $classLists->whereHas('time', fn ($query) => $query->where('time_name', $request->time));
        }

        $classLists = $classLists->latest()->paginate(20)->withQueryString();

        return Inertia::render('backend/classes/class-list/ClassList', [
            'classLists' => $classLists,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? '',
                'class_type' => $request->class_type ?? '',
                'term' => $request->term ?? '',
                'time' => $request->time ?? '',
            ],
            'classTypes' => ClassType::select('class_type_id', 'type_name')->get(),
            'terms' => Term::select('id', 'term_name')->get(),
            'times' => Time::select('id', 'time_name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'teacher_id'    => ['nullable', 'exists:users,id'],
            'course_id'     => ['required', 'exists:courses,id'],
            'lesson_id'     => ['nullable', 'exists:course_lessons,id'],
            'term_id'       => ['nullable', 'exists:terms,id'],
            'time_id'       => ['nullable', 'exists:times,id'],
            'room_id'       => ['nullable', 'exists:rooms,id'],
            'class_type_id' => ['nullable', 'exists:class_type,class_type_id'],
            'capacity'      => ['nullable', 'integer', 'min:0'],
            'status'        => ['nullable', 'string', Rule::in(GetClassFormOptions::STATUSES)],
        ]);

        StudyClass::create($validated);

        return redirect()->route('class-list.index')->with('success', 'Class created successfully.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('backend/classes/class-list/ClassListCreate', [
            'teachers' => User::role('instructor')->select('id', 'name')->get(),
            'courses' => Course::select('id', 'title')->get(),
            'lessons' => CourseLesson::select('id', 'title')->get(),
            'terms' => Term::select('id', 'term_name')->get(),
            'times' => Time::select('id', 'time_name')->get(),
            'rooms' => Room::select('id', 'room_number')->get(),
            'classTypes' => ClassType::select('class_type_id', 'type_name')->get(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(StudyClass $classList)
    {
        $classList->load(['teacher', 'course', 'lesson', 'term', 'time', 'room.floor.building', 'classType']);
        $classList->loadCount([
            'enrollments as current_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
        ]);

        return Inertia::render('backend/classes/class-list/ClassListShow', [
            'classList' => $classList,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StudyClass $classList)
    {
        return Inertia::render('backend/classes/class-list/ClassListEdit', [
            'classList' => $classList->load(['course', 'lesson', 'term', 'time', 'room', 'classType']),
            'teachers' => User::role('instructor')->select('id', 'name')->get(),
            'courses' => Course::select('id', 'title')->get(),
            'lessons' => CourseLesson::select('id', 'title')->get(),
            'terms' => Term::select('id', 'term_name')->get(),
            'times' => Time::select('id', 'time_name')->get(),
            'rooms' => Room::select('id', 'room_number')->get(),
            'classTypes' => ClassType::select('class_type_id', 'type_name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StudyClass $classList)
    {
        $validated = $request->validate([
            'title'         => ['sometimes', 'required', 'string', 'max:255'],
            'teacher_id'    => ['sometimes', 'nullable', 'exists:users,id'],
            'course_id'     => ['sometimes', 'required', 'exists:courses,id'],
            'lesson_id'     => ['sometimes', 'nullable', 'exists:course_lessons,id'],
            'term_id'       => ['sometimes', 'nullable', 'exists:terms,id'],
            'time_id'       => ['sometimes', 'nullable', 'exists:times,id'],
            'room_id'       => ['sometimes', 'nullable', 'exists:rooms,id'],
            'class_type_id' => ['sometimes', 'nullable', 'exists:class_type,class_type_id'],
            'capacity'      => ['sometimes', 'nullable', 'integer', 'min:0'],
            'status'        => ['sometimes', 'nullable', 'string', Rule::in(GetClassFormOptions::STATUSES)],
        ]);

        $classList->update($validated);

        return redirect()->route('class-list.index')->with('success', 'Class updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudyClass $classList)
    {
        $classList->delete();

        return redirect()->route('class-list.index')->with('success', 'Class deleted successfully.');
    }
}
