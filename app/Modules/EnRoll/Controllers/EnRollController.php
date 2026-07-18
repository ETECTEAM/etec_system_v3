<?php

namespace App\Modules\EnRoll\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Enrollment;
use App\Models\Floor;
use App\Models\Room;
use App\Models\ScheduleClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\Time;
use App\Modules\EnRoll\Requests\StoreClassRequest;
use App\Modules\EnRoll\Requests\UpdateClassRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnRollController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        $classes = ScheduleClass::query()
            ->with([
                'course:id,title',
                'lesson:id,title',
                'building:id,name',
                'floor:id,name',
                'room:id,room_number',
                'term:id,term_name',
                'time:id,time_name',
            ])
            ->withCount('enrollments as students_count')
            ->search($search)
            ->sortBy($sortField, $sortDirection)
            ->paginate(12)
            ->through(fn (ScheduleClass $class) => [
                'id'       => $class->id,
                'title'    => $class->title,
                'lesson'   => $class->lesson?->title,
                'building' => $class->building?->name,
                'floor'    => $class->floor?->name,
                'room'     => $class->room?->room_number,
                'status'   => $class->status,
                'term'     => $class->term?->term_name,
                'time'     => $class->time?->time_name,
                'students' => (int) $class->students_count,
                'capacity' => $class->capacity,
            ]);

        return Inertia::render('backend/students/ClassList', [
            'classes' => $classes,
            'filters' => [
                'search'    => $search,
                'sort'      => $sortField,
                'direction' => $sortDirection,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('backend/students/CreateClass', [
            'courses'  => Course::select('id', 'title')->get(),
            'lessons'  => CourseLesson::select('id', 'title')->get(),
            'buildings'=> Building::select('id', 'name')->get(),
            'floors'   => Floor::select('id', 'name')->get(),
            'rooms'    => Room::select('id', 'room_number')->get(),
            'terms'    => Term::select('id', 'term_name')->get(),
            'times'    => Time::select('id', 'time_name')->get(),
        ]);
    }

    public function store(StoreClassRequest $request): RedirectResponse
    {
        ScheduleClass::create($request->validated());

        return redirect()
            ->route('students.index')
            ->with('success', 'Class created successfully.');
    }

    public function show(ScheduleClass $class): Response
    {
        $class->load([
            'course:id,title',
            'lesson:id,title',
            'building:id,name',
            'floor:id,name',
            'room:id,room_number',
            'term:id,term_name',
            'time:id,time_name',
        ]);

        $class->loadCount('enrollments as students_count');

        $students = Student::whereHas('enrollments', function ($q) use ($class) {
            $q->where('class_id', $class->id)->where('status', 'active');
        })->get(['id', 'full_name', 'gender', 'phone']);

        return Inertia::render('backend/students/ViewClass', [
            'classData' => [
                'id'       => $class->id,
                'title'    => $class->title,
                'course'   => $class->course?->title,
                'lesson'   => $class->lesson?->title,
                'building' => $class->building?->name,
                'floor'    => $class->floor?->name,
                'room'     => $class->room?->room_number,
                'status'   => $class->status,
                'term'     => $class->term?->term_name,
                'time'     => $class->time?->time_name,
                'capacity' => $class->capacity,
                'students' => (int) $class->students_count,
            ],
            'enrolledStudents' => $students,
        ]);
    }

    public function edit(ScheduleClass $class): Response
    {
        $class->load([
            'course:id,title',
            'lesson:id,title',
            'building:id,name',
            'floor:id,name',
            'room:id,room_number',
            'term:id,term_name',
            'time:id,time_name',
        ]);

        return Inertia::render('backend/students/EditClass', [
            'classData' => [
                'id'          => $class->id,
                'title'       => $class->title,
                'course_id'   => $class->course_id,
                'lesson_id'   => $class->lesson_id,
                'building_id' => $class->building_id,
                'floor_id'    => $class->floor_id,
                'room_id'     => $class->room_id,
                'term_id'     => $class->term_id,
                'time_id'     => $class->time_id,
                'capacity'    => $class->capacity,
                'status'      => $class->status,
            ],
            'courses'  => Course::select('id', 'title')->get(),
            'lessons'  => CourseLesson::select('id', 'title')->get(),
            'buildings'=> Building::select('id', 'name')->get(),
            'floors'   => Floor::select('id', 'name')->get(),
            'rooms'    => Room::select('id', 'room_number')->get(),
            'terms'    => Term::select('id', 'term_name')->get(),
            'times'    => Time::select('id', 'time_name')->get(),
        ]);
    }

    public function update(UpdateClassRequest $request, ScheduleClass $class): RedirectResponse
    {
        $class->update($request->validated());

        return redirect()
            ->route('students.index')
            ->with('success', 'Class updated successfully.');
    }

    public function destroy(ScheduleClass $class): RedirectResponse
    {
        Enrollment::where('class_id', $class->id)->delete();
        $class->delete();

        return redirect()
            ->route('students.index')
            ->with('success', 'Class deleted successfully.');
    }
}
