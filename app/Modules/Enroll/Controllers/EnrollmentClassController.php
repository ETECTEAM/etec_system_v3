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
use App\Modules\Enroll\Actions\RegisterStudent;
use App\Modules\Enroll\Actions\UpdatePublicRegistrationDetails;
use App\Modules\Enroll\Actions\UpdateStudyClass;
use App\Modules\Enroll\Queries\GetClassDetails;
use App\Modules\Enroll\Queries\GetClassFormOptions;
use App\Modules\Enroll\Queries\GetClassList;
use App\Modules\Enroll\Queries\GetPublicRegistrations;
use App\Modules\Enroll\Requests\EnrollStudentRequest;
use App\Modules\Enroll\Requests\RecordDepositRequest;
use App\Modules\Enroll\Requests\RegisterStudentRequest;
use App\Modules\Enroll\Requests\SaveStudyClassRequest;
use App\Modules\Enroll\Requests\StoreClassStudentRequest;
use App\Modules\Enroll\Requests\UpdatePublicRegistrationRequest;
use App\Modules\Website\Actions\RegisterStudentForSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentClassController extends Controller
{
    public function index(Request $request, GetClassList $classes): Response
    {
        return Inertia::render('backend/students/ClassList', $classes->handle($request));
    }

    public function publicRegistrations(GetPublicRegistrations $query): JsonResponse
    {
        return response()->json(['data' => $query->handle()]);
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

        // "Save & Copy": keep the user on the create form (front end preserves its state)
        // so they can duplicate the class with a different term/time/teacher.
        if ($request->boolean('create_another')) {
            return redirect()->route('enroll.create')->with('success', 'Class created. Adjust the details and save again to create another.');
        }

        return redirect()->route('enroll.index')->with('success', 'Class created successfully.');
    }

    public function show(StudyClass $studyClass, GetClassDetails $details): Response
    {
        return Inertia::render('backend/students/ViewClass', [
            ...$details->handle($studyClass),
            'studentsForSelect' => $this->studentsForSelect(),
        ]);
    }

    public function edit(
        StudyClass $studyClass,
        GetClassFormOptions $options,
        RegisterStudentForSchedule $registrations
    ): Response
    {
        $this->ensureInstructorOwnsClass($studyClass);
        $registrations->repairClass($studyClass);

        $studyClass->load([
            'room.floor.building',
            'classType:class_type_id,type_name',
            'term:id,term_name',
            'time:id,time_name',
        ]);

        return Inertia::render('backend/students/EditClass', [
            'classData' => [
                'id' => $studyClass->id,
                ...$this->presentClassData($studyClass),
            ],
            'options' => $options->handle($studyClass),
        ]);
    }

    public function copy(
        StudyClass $studyClass,
        GetClassFormOptions $options,
        RegisterStudentForSchedule $registrations
    ): Response
    {
        $this->ensureInstructorOwnsClass($studyClass);
        $registrations->repairClass($studyClass);

        $studyClass->load([
            'room.floor.building',
            'classType:class_type_id,type_name',
            'term:id,term_name',
            'time:id,time_name',
        ]);

        return Inertia::render('backend/students/CreateClass', [
            'classData' => $this->presentClassData($studyClass),
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

    public function createRegisteredStudent(GetClassFormOptions $options): Response
    {
        return Inertia::render('backend/students/RegisterStudent', [
            'courses' => Course::query()->select('id', 'title')->orderBy('title')->get(),
            'scheduleGroups' => $options->scheduleGroups(),
        ]);
    }

    public function storeRegisteredStudent(
        RegisterStudentRequest $request,
        RegisterStudent $registerStudent
    ): RedirectResponse {
        $registerStudent->handle($request->validated());

        return redirect()
            ->route('enroll.students.create')
            ->with('success', 'Student registered successfully.');
    }

    public function update(
        SaveStudyClassRequest $request,
        StudyClass $studyClass,
        UpdateStudyClass $updateStudyClass
    ): RedirectResponse {
        $this->ensureInstructorOwnsClass($studyClass);

        $updateStudyClass->handle($studyClass, $request->validated());

        return redirect()->route('enroll.index')->with('success', 'Class updated successfully.');
    }

    public function updateStatus(Request $request, StudyClass $studyClass): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:upcoming,active,pre_end,ended,cancelled,inactive,completed'],
        ]);

        $statusMap = [
            'inactive' => 'pre_end',
            'completed' => 'ended',
        ];

        $studyClass->update([
            'status' => $statusMap[$validated['status']] ?? $validated['status'],
        ]);

        return back()->with('success', 'Class status updated successfully.');
    }

    public function destroy(StudyClass $studyClass): RedirectResponse
    {
        $studyClass->enrollments()->delete();
        $studyClass->delete();

        return redirect()->route('enroll.index')->with('success', 'Class deleted successfully.');
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
            ->route('enroll.class-students.create', $studyClass)
            ->with('success', 'Student added to class successfully.');
    }

    public function deposit(
        RecordDepositRequest $request,
        StudentEnrollment $enrollment,
        RecordEnrollmentDeposit $recordDeposit
    ): RedirectResponse|JsonResponse {
        $enrollment = $recordDeposit->handle($enrollment, (float) $request->validated('deposit_amount'));

        if ($request->expectsJson()) {
            $totalDue = (float) $enrollment->fee_amount + (float) $enrollment->document_fee_amount;

            return response()->json([
                'amount_paid' => (float) $enrollment->amount_paid,
                'payment_status' => $enrollment->payment_status,
                'remaining_balance' => max($totalDue - (float) $enrollment->amount_paid, 0),
            ]);
        }

        return back()->with('success', 'Deposit recorded successfully.');
    }

    public function updateRegistration(
        UpdatePublicRegistrationRequest $request,
        StudentEnrollment $enrollment,
        UpdatePublicRegistrationDetails $updateDetails
    ): JsonResponse {
        $updateDetails->handle($enrollment, $request->validated());

        return response()->json(['success' => true]);
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

    private function presentClassData(StudyClass $studyClass): array
    {
        return [
            'title' => $studyClass->title,
            'course_id' => $studyClass->course_id,
            'lesson_id' => $studyClass->lesson_id,
            'teacher_id' => $studyClass->teacher_id,
            'building_id' => $studyClass->room?->floor?->building?->id,
            'floor_id' => $studyClass->room?->floor?->id,
            'room_id' => $studyClass->room_id,
            'class_type_id' => $studyClass->class_type_id,
            'term_id' => $studyClass->term_id,
            'time_id' => $studyClass->time_id,
            'class_type' => $studyClass->classTypeValue(),
            'status' => $studyClass->status,
            'study_days' => $studyClass->scheduleStudyDays(),
            'start_time' => $this->formatTime($studyClass->scheduleStartTime()),
            'end_time' => $this->formatTime($studyClass->scheduleEndTime()),
            'capacity' => $studyClass->capacity,
            'price' => round((float) $studyClass->price, 2),
            'document_price' => round((float) $studyClass->document_price, 2),
            'enrollment_start_date' => $studyClass->enrollment_start_date?->format('Y-m-d'),
            'start_date' => $studyClass->start_date?->format('Y-m-d'),
            'end_date' => $studyClass->end_date?->format('Y-m-d'),
        ];
    }

    private function formatTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }

    /**
     * Instructors may only view/edit classes assigned to them; admins/super admins
     * (who assign classes to instructors, not to themselves) are unrestricted.
     */
    private function ensureInstructorOwnsClass(StudyClass $studyClass): void
    {
        $user = auth()->user();

        if ($user->hasRole('instructor') && ! $user->hasAnyRole(['admin', 'super_admin'])) {
            abort_unless($studyClass->teacher_id === $user->id, 403, 'You can only manage classes assigned to you.');
        }
    }
}
