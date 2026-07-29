<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\ClassList;
use App\Models\ClassType;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Floor;
use App\Models\Room;
use App\Models\Term;
use App\Models\Time;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InstructorClassController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('backend/instructors/CreateClass', [
            'courses' => Course::select('id', 'title')->get(),
            'lessons' => CourseLesson::select('id', 'title')->get(),
            'terms' => Term::select('id', 'term_name')->get(),
            'times' => Time::select('id', 'time_name')->get(),
            'buildings' => Building::select('id', 'name')->get(),
            'floors' => Floor::select('id', 'name')->get(),
            'rooms' => Room::select('id', 'room_number')->get(),
            'classTypes' => ClassType::select('class_type_id', 'type_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id'     => ['nullable', 'exists:courses,id'],
            'lesson_id'     => ['nullable', 'exists:course_lessons,id'],
            'term_id'       => ['nullable', 'exists:terms,id'],
            'time_id'       => ['nullable', 'exists:times,id'],
            'building_id'   => ['nullable', 'exists:buildings,id'],
            'floor_id'      => ['nullable', 'exists:floors,id'],
            'room_id'       => ['nullable', 'exists:rooms,id'],
            'class_type_id' => ['nullable', 'exists:class_type,class_type_id'],
            'student_count' => ['nullable', 'integer', 'min:0'],
            'status'        => ['nullable', 'string', Rule::in(['progress', 'completed', 'cancelled'])],
        ]);

        // Instructors can only ever create classes for themselves, regardless
        // of what's in the request body.
        $validated['teacher_id'] = $request->user()->id;

        ClassList::create($validated);

        return redirect()->route('dashboard')->with('success', 'Class created successfully.');
    }
}
