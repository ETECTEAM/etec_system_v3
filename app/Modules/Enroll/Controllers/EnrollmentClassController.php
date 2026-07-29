<?php

namespace App\Modules\Enroll\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Course;
use App\Models\Floor;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\User;
use App\Modules\Enroll\Actions\CreateClassStudent;
use App\Modules\Enroll\Actions\CreateStudyClass;
use App\Modules\Enroll\Actions\EnrollStudent;
use App\Modules\Enroll\Actions\RecordEnrollmentDeposit;
use App\Modules\Enroll\Actions\UpdateStudyClass;
use App\Modules\Enroll\Queries\GetClassDetails;
use App\Modules\Enroll\Queries\GetClassFormOptions;
use App\Modules\Enroll\Queries\GetClassList;
use App\Modules\Enroll\Requests\EnrollStudentRequest;
use App\Modules\Enroll\Requests\RecordDepositRequest;
use App\Modules\Enroll\Requests\SaveStudyClassRequest;
use App\Modules\Enroll\Requests\StoreClassStudentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnRollController extends Controller
{
    public function index(Request $request, GetClassList $classes): Response
    {
        return Inertia::render('backend/students/ClassList', $classes->handle($request));
    }

    public function create(GetClassFormOptions $options): Response
    {
        return Inertia::render('backend/students/CreateClass', [
            'options' => $options->handle(),
        ]);
    }

    public function store(SaveStudyClassRequest $request, CreateStudyClass $createStudyClass): RedirectResponse
    {
        $createStudyClass->handle($request->validated());

        return redirect()->route('students.index')->with('success', 'Class created successfully.');
    }

    public function show(StudyClass $studyClass, GetClassDetails $details): Response
    {
        return Inertia::render('backend/students/ViewClass', [
            ...$details->handle($studyClass),
            'studentsForSelect' => $this->studentsForSelect(),
        ]);
    }

    public function edit(StudyClass $studyClass, GetClassFormOptions $options): Response
    {
        $studyClass->load(['room.floor.building']);

        return Inertia::render('backend/students/EditClass', [
            'classData' => [
                'id' => $studyClass->id,
                'title' => $studyClass->title,
                'course_id' => $studyClass->course_id,
                'lesson_id' => $studyClass->lesson_id,
                'teacher_id' => $studyClass->teacher_id,
                'building_id' => $studyClass->room?->floor?->building?->id,
                'floor_id' => $studyClass->room?->floor?->id,
                'room_id' => $studyClass->room_id,
                'class_type' => $studyClass->class_type,
                'status' => $studyClass->status,
                'study_days' => $studyClass->study_days ?? [],
                'start_time' => $this->formatTime($studyClass->start_time),
                'end_time' => $this->formatTime($studyClass->end_time),
                'capacity' => $studyClass->capacity,
                'price' => (float) $studyClass->price,
                'enrollment_start_date' => $studyClass->enrollment_start_date?->format('Y-m-d'),
                'start_date' => $studyClass->start_date?->format('Y-m-d'),
                'end_date' => $studyClass->end_date?->format('Y-m-d'),
            ],
            'options' => $options->handle($studyClass),
        ]);
    }

    public function createStudent(StudyClass $studyClass, GetClassList $classList): Response
    {
        $studyClass->load([
            'course:id,title',
            'lesson:id,course_id,title',
            'teacher:id,name',
            'room:id,floor_id,room_number',
            'room.floor:id,building_id,name,level',
            'room.floor.building:id,name',
        ])->loadCount([
            'enrollments as current_students' => fn ($query) => $query->where('enrollment_status', 'active'),
        ]);

        return Inertia::render('backend/students/CreateStudent', [
            'classData' => $classList->presentClass($studyClass),
        ]);
    }

    public function update(
        SaveStudyClassRequest $request,
        StudyClass $studyClass,
        UpdateStudyClass $updateStudyClass
    ): RedirectResponse {
        $updateStudyClass->handle($studyClass, $request->validated());

        return redirect()->route('students.index')->with('success', 'Class updated successfully.');
    }

    public function floors(Building $building, GetClassFormOptions $options): JsonResponse
    {
        return response()->json($options->floors($building->id));
    }

    public function rooms(Floor $floor, GetClassFormOptions $options): JsonResponse
    {
        return response()->json($options->rooms($floor->id));
    }

    public function lessons(Course $course, GetClassFormOptions $options): JsonResponse
    {
        return response()->json($options->lessons($course->id));
    }

    public function enroll(
        EnrollStudentRequest $request,
        StudyClass $studyClass,
        EnrollStudent $enrollStudent
    ): RedirectResponse {
        $enrollStudent->handle($studyClass, (int) $request->validated('student_id'));

        return back()->with('success', 'Student added to class successfully.');
    }

    public function storeStudent(
        StoreClassStudentRequest $request,
        StudyClass $studyClass,
        CreateClassStudent $createClassStudent
    ): RedirectResponse {
        $createClassStudent->handle($studyClass, $request->validated());

        return redirect()
            ->route('students.class-students.create', $studyClass)
            ->with('success', 'Student added to class successfully.');
    }

    public function deposit(
        RecordDepositRequest $request,
        StudentEnrollment $enrollment,
        RecordEnrollmentDeposit $recordDeposit
    ): RedirectResponse {
        $recordDeposit->handle($enrollment, (float) $request->validated('deposit_amount'));

        return back()->with('success', 'Deposit recorded successfully.');
    }

    private function studentsForSelect(): array
    {
        return User::query()
            ->whereHas('student')
            ->with('student:id,user_id,phone')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->student?->phone,
            ])
            ->all();
    }

    private function formatTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }
}
