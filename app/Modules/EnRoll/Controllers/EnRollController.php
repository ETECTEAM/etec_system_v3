<?php

namespace App\Modules\EnRoll\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\ClassType;
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
use Illuminate\Http\JsonResponse;
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
        return Inertia::render('backend/students/CreateClass', $this->classFormProps());
    }

    public function store(StoreClassRequest $request): RedirectResponse
    {
        ScheduleClass::create($request->validated());

        return redirect()
            ->route('students.index')
            ->with('success', 'Class created successfully.');
    }

    public function show(ScheduleClass $studyClass): Response
    {
        $studyClass->load([
            'course:id,title',
            'lesson:id,title',
            'building:id,name',
            'floor:id,name',
            'room:id,room_number',
            'term:id,term_name',
            'time:id,time_name',
        ]);

        $studyClass->loadCount('enrollments as students_count');

        $students = Enrollment::query()
            ->with('student:id,full_name,gender,phone')
            ->where('class_id', $studyClass->id)
            ->where('status', 'active')
            ->latest()
            ->get()
            ->map(fn (Enrollment $enrollment): array => $this->presentEnrolledStudent($enrollment))
            ->all();

        return Inertia::render('backend/students/ViewClass', [
            'classData' => $this->presentClass($studyClass),
            'students' => $students,
        ]);
    }

    public function edit(ScheduleClass $studyClass): Response
    {
        $studyClass->load([
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
                'id'          => $studyClass->id,
                'title'       => $studyClass->title,
                'course_id'   => $studyClass->course_id,
                'lesson_id'   => $studyClass->lesson_id,
                'building_id' => $studyClass->building_id,
                'floor_id'    => $studyClass->floor_id,
                'room_id'     => $studyClass->room_id,
                'term_id'     => $studyClass->term_id,
                'time_id'     => $studyClass->time_id,
                'capacity'    => $studyClass->capacity,
                'status'      => $studyClass->status,
            ],
            ...$this->classFormProps($studyClass),
        ]);
    }

    public function createStudent(ScheduleClass $studyClass): Response
    {
        $studyClass->loadCount('enrollments as students_count');

        return Inertia::render('backend/students/CreateStudent', [
            'classData' => $this->presentClass($studyClass),
        ]);
    }

    public function storeStudent(Request $request, ScheduleClass $studyClass): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:male,female'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $student = Student::query()->firstOrCreate(
            ['phone' => $data['phone']],
            [
                'user_id' => $request->user()->id,
                'full_name' => $data['name'],
                'gender' => $data['gender'],
            ]
        );

        $student->update([
            'full_name' => $data['name'],
            'gender' => $data['gender'],
        ]);

        Enrollment::query()->firstOrCreate(
            [
                'student_id' => $student->id,
                'class_id' => $studyClass->id,
            ],
            [
                'status' => 'active',
                'enrollment_date' => now()->toDateString(),
            ]
        );

        return redirect()
            ->route('students.show', $studyClass)
            ->with('success', 'Student added successfully.');
    }

    public function floors(Building $building): JsonResponse
    {
        return response()->json($this->floorsForBuilding($building->id));
    }

    public function rooms(Floor $floor): JsonResponse
    {
        return response()->json($this->roomsForFloor($floor->id));
    }

    public function lessons(Course $course): JsonResponse
    {
        return response()->json($this->lessonsForCourse($course->id));
    }

    public function update(UpdateClassRequest $request, ScheduleClass $studyClass): RedirectResponse
    {
        $studyClass->update($request->validated());

        return redirect()
            ->route('students.index')
            ->with('success', 'Class updated successfully.');
    }

    public function updateStatus(Request $request, ScheduleClass $studyClass): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:active,inactive,completed'],
        ]);

        $studyClass->update(['status' => $data['status']]);

        return back()->with('success', 'Class status updated successfully.');
    }

    public function destroy(ScheduleClass $studyClass): RedirectResponse
    {
        Enrollment::where('class_id', $studyClass->id)->delete();
        $studyClass->delete();

        return redirect()
            ->route('students.index')
            ->with('success', 'Class deleted successfully.');
    }

    public function enroll(Request $request, ScheduleClass $studyClass): RedirectResponse
    {
        return $this->storeStudent($request, $studyClass);
    }

    public function deposit(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $request->validate([
            'deposit_amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        return back()->with('success', 'Deposit recorded successfully.');
    }

    private function presentClass(ScheduleClass $class): array
    {
        return [
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
            'students' => (int) ($class->students_count ?? 0),
        ];
    }

    private function presentEnrolledStudent(Enrollment $enrollment): array
    {
        $student = $enrollment->student;

        return [
            'id' => $student?->id,
            'enrollment_id' => $enrollment->id,
            'name' => $student?->full_name,
            'gender' => $student?->gender,
            'phone' => $student?->phone,
            'deposit_amount' => 0,
            'amount_paid' => 0,
            'payment_date' => null,
            'payment_status' => 'Unpaid',
            'remaining_balance' => 0,
        ];
    }

    private function classFormProps(?ScheduleClass $class = null): array
    {
        $options = [
            'courses' => Course::query()->select('id', 'title', 'price')->orderBy('title')->get(),
            'lessons' => $class?->course_id ? $this->lessonsForCourse($class->course_id) : [],
            'buildings' => Building::query()->select('id', 'name')->orderBy('name')->get(),
            'floors' => $class?->building_id ? $this->floorsForBuilding($class->building_id) : [],
            'rooms' => $class?->floor_id ? $this->roomsForFloor($class->floor_id) : [],
            'terms' => Term::query()->select('id', 'term_name')->orderBy('term_name')->get(),
            'times' => Time::query()->select('id', 'time_name')->orderBy('time_name')->get(),
            'classTypes' => $this->classTypes(),
            'studyDays' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        ];

        return [
            ...$options,
            'options' => $options,
        ];
    }

    private function floorsForBuilding(int $buildingId): array
    {
        return Floor::query()
            ->where('building_id', $buildingId)
            ->select('id', 'building_id', 'name', 'level')
            ->orderBy('level')
            ->orderBy('name')
            ->get()
            ->all();
    }

    private function roomsForFloor(int $floorId): array
    {
        return Room::query()
            ->where('floor_id', $floorId)
            ->select('id', 'floor_id', 'room_number', 'capacity')
            ->orderBy('room_number')
            ->get()
            ->all();
    }

    private function lessonsForCourse(int $courseId): array
    {
        return CourseLesson::query()
            ->where('course_id', $courseId)
            ->select('id', 'course_id', 'title')
            ->orderBy('order_number')
            ->orderBy('title')
            ->get()
            ->all();
    }

    private function classTypes(): array
    {
        $types = ClassType::query()
            ->where('is_active', true)
            ->select('class_type_id', 'type_name')
            ->orderBy('class_type_id')
            ->get()
            ->map(function (ClassType $type): ?array {
                $name = strtolower($type->type_name);

                if (str_contains($name, 'online')) {
                    return ['value' => 'online', 'label' => $type->type_name];
                }

                if (str_contains($name, 'physical')) {
                    return ['value' => 'physical', 'label' => $type->type_name];
                }

                return null;
            })
            ->filter()
            ->unique('value')
            ->values()
            ->all();

        return $types ?: [
            ['value' => 'physical', 'label' => 'Physical Class'],
            ['value' => 'online', 'label' => 'Online Class'],
        ];
    }
}
