<?php

namespace App\Modules\Enroll\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\Floor;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\User;
use App\Modules\Enroll\Actions\CreateClassStudent;
use App\Modules\Enroll\Actions\CreateStudyClass;
use App\Modules\Enroll\Actions\EnrollStudent;
use App\Modules\Enroll\Actions\MoveStudentEnrollment;
use App\Modules\Enroll\Actions\RecordEnrollmentDeposit;
use App\Modules\Enroll\Actions\RegisterStudent;
use App\Modules\Enroll\Actions\ShareClassWithInstructor;
use App\Modules\Enroll\Actions\UpdatePublicRegistrationDetails;
use App\Modules\Enroll\Actions\UpdateStudyClass;
use App\Modules\Enroll\Queries\GetClassDetails;
use App\Modules\Enroll\Queries\GetClassFormOptions;
use App\Modules\Enroll\Queries\GetClassList;
use App\Modules\Enroll\Queries\GetPublicRegistrations;
use App\Modules\Enroll\Requests\EnrollStudentRequest;
use App\Modules\Enroll\Requests\MoveEnrollmentRequest;
use App\Modules\Enroll\Requests\RecordDepositRequest;
use App\Modules\Enroll\Requests\RegisterStudentRequest;
use App\Modules\Enroll\Requests\SaveStudyClassRequest;
use App\Modules\Enroll\Requests\ShareClassInstructorRequest;
use App\Modules\Enroll\Requests\StoreClassStudentRequest;
use App\Modules\Enroll\Requests\UpdatePublicRegistrationRequest;
use App\Modules\Enroll\Services\StudentRegistrationService;
use App\Modules\Website\Actions\RegisterStudentForSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentClassController extends Controller
{
    public function index(Request $request, GetClassList $classes): Response
    {
        return Inertia::render('backend/students/ClassList', $classes->handle($request));
    }

    public function publicRegistrations(Request $request, GetPublicRegistrations $query): JsonResponse
    {
        $registrations = $query->handle($request);
        $search = $request->string('search')->toString();

        return response()->json(array_merge($registrations->toArray(), [
            'pending_count' => $query->pendingCount($search),
        ]));
    }

    // Lightweight, unpaginated class list for the "move student to another
    // class" picker on the Registrations tab.
    public function classesForSelect(GetClassList $classes): JsonResponse
    {
        return response()->json(['data' => $classes->forSelect()]);
    }

    // Moves a public registration's student into a different existing class
    // (e.g. joining a friend's class instead of the one auto-assigned to
    // them), optionally overriding that class's capacity. Also handles the
    // first assignment for a registration RegisterStudentForSchedule parked
    // with no class at all (enrollment.study_class_id null - see
    // no_room_and_instructor/no_instructor/no_room) - MoveStudentEnrollment
    // assigns those in place instead of cancelling a "move" that never had a
    // real source class.
    public function moveRegistration(
        MoveEnrollmentRequest $request,
        StudentEnrollment $enrollment,
        MoveStudentEnrollment $moveStudentEnrollment
    ): JsonResponse {
        $targetClass = StudyClass::query()->findOrFail($request->validated('study_class_id'));

        $moveStudentEnrollment->handle($enrollment, $targetClass, $request->boolean('force'));

        return response()->json(['success' => true]);
    }

    public function create(GetClassFormOptions $options): Response
    {
        return Inertia::render('backend/students/CreateClass', [
            'options' => $options->handle(),
        ]);
    }

    public function store(SaveStudyClassRequest $request, CreateStudyClass $createStudyClass): RedirectResponse
    {
        $data = $request->validated();

        // Instructors only reach this by copying one of their own classes, so the copy stays
        // theirs — picking which instructor a class belongs to is an admin-only decision.
        if ($this->isSelfManagingInstructor()) {
            $data['teacher_id'] = $request->user()->id;
        }

        $createStudyClass->handle($data);

        // "Save & Copy": keep the user on the create form (front end preserves its state)
        // so they can duplicate the class with a different term/time/teacher.
        if ($request->boolean('create_another')) {
            $message = 'Class created. Adjust the details and save again to create another.';

            return $this->isSelfManagingInstructor()
                ? back()->with('success', $message)
                : redirect()->route('enroll.create')->with('success', $message);
        }

        return $this->redirectToClassList()->with('success', 'Class created successfully.');
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

    public function createStudent(StudyClass $studyClass, GetClassList $classList): Response|RedirectResponse
    {
        if (auth()->guest()) {
            return redirect()->route('frontend.class-join.create', $studyClass);
        }

        $this->ensureClassAcceptsMutations($studyClass);

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

        return $this->redirectToClassList()->with('success', 'Class updated successfully.');
    }

    public function updateStatus(Request $request, StudyClass $studyClass): RedirectResponse
    {
        $this->ensureInstructorOwnsClass($studyClass);

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

    /**
     * Everything the "Collapse Class" dialog needs to split a class between two
     * instructors: who already teaches it, who else could, and the schedules on offer.
     */
    public function instructors(StudyClass $studyClass, GetClassFormOptions $options): JsonResponse
    {
        $this->ensureInstructorOwnsClass($studyClass);

        $studyClass->load(['instructors:id,name', 'teacher:id,name']);

        return response()->json([
            'owner' => $studyClass->teacher ? [
                'id' => $studyClass->teacher->id,
                'name' => $studyClass->teacher->name,
            ] : null,
            'classTypeId' => $studyClass->class_type_id,
            'termId' => $studyClass->term_id,
            'timeId' => $studyClass->time_id,
            'shared' => $studyClass->instructors->map(fn (User $instructor) => [
                'id' => $instructor->id,
                'name' => $instructor->name,
                'term_id' => $instructor->pivot->term_id,
                'time_id' => $instructor->pivot->time_id,
                'subject' => $instructor->pivot->subject,
                'is_owner' => $instructor->id === $studyClass->teacher_id,
            ])->values(),
            'teachers' => User::role('instructor')
                ->where('id', '!=', $studyClass->teacher_id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'schedules' => $this->shareableSchedules($studyClass, $options),
        ]);
    }

    /**
     * Terms a shared class can be split by. The day-splits an instructor actually teaches
     * (Mon & Tue, Wed & Thu, ...) are configured under the Basic class type; the other
     * types only carry the generic terms used on the receipt. Falls back to the class's
     * own type when Basic isn't configured, and drops the receipt terms either way —
     * splitting a class across them would put both instructors on the same days.
     */
    private function shareableSchedules(StudyClass $studyClass, GetClassFormOptions $options): array
    {
        $groups = collect($options->scheduleGroups());

        $group = $groups->first(fn (array $item) => strtolower($item['class_type_name'] ?? '') === 'basic')
            ?? $groups->first(fn (array $item) => (int) $item['class_type_id'] === (int) $studyClass->class_type_id);

        return collect($group['schedules'] ?? [])
            ->reject(fn ($schedule) => in_array($schedule['term_name'], GetClassFormOptions::RECEIPT_ONLY_TERMS, true))
            ->values()
            ->all();
    }

    public function shareWithInstructor(
        ShareClassInstructorRequest $request,
        StudyClass $studyClass,
        ShareClassWithInstructor $shareClass
    ): RedirectResponse {
        $this->ensureClassAcceptsMutations($studyClass);
        $this->ensureInstructorOwnsClass($studyClass);

        $shareClass->handle($studyClass, $request->validated());

        return back()->with('success', 'Class shared with the instructor successfully.');
    }

    public function removeInstructor(
        StudyClass $studyClass,
        User $user,
        ShareClassWithInstructor $shareClass
    ): RedirectResponse {
        $this->ensureClassAcceptsMutations($studyClass);
        $this->ensureInstructorOwnsClass($studyClass);

        $shareClass->remove($studyClass, $user->id);

        return back()->with('success', 'Instructor removed from the class successfully.');
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
        $this->ensureClassAcceptsMutations($studyClass);

        $enrollStudent->handle(
            $studyClass,
            (int) $request->validated('student_id'),
            $request->boolean('force'),
        );

        return back()->with('success', 'Student added to class successfully.');
    }

    public function storeStudent(
        StoreClassStudentRequest $request,
        StudyClass $studyClass,
        CreateClassStudent $createClassStudent
    ): RedirectResponse {
        $this->ensureClassAcceptsMutations($studyClass);

        $createClassStudent->handle($studyClass, $request->validated());

        return redirect()
            ->route('enroll.class-students.create', $studyClass)
            ->with('success', 'Student added to class successfully.');
    }

    public function approveEnrollment(Request $request, StudentEnrollment $enrollment, StudentRegistrationService $registrations): RedirectResponse
    {
        DB::transaction(function () use ($enrollment, $registrations): void {
            $enrollment->loadMissing('studyClass');
            $this->approvePendingEnrollment($enrollment, $registrations);
        }, 1);

        return back()->with('success', 'Student request approved successfully.');
    }

    public function approveEnrollments(Request $request, StudentRegistrationService $registrations): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['required', 'integer', 'distinct', 'exists:student_enrollments,id'],
        ]);

        $approvedCount = 0;

        DB::transaction(function () use (&$approvedCount, $validated, $registrations): void {
            $enrollments = StudentEnrollment::query()
                ->whereIn('id', $validated['enrollment_ids'])
                ->with('studyClass')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($validated['enrollment_ids'] as $enrollmentId) {
                $enrollment = $enrollments->get((int) $enrollmentId);

                abort_unless($enrollment !== null, 404, 'One of the selected requests could not be found.');

                $this->approvePendingEnrollment($enrollment, $registrations);
                $approvedCount++;
            }
        }, 1);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'approved_count' => $approvedCount,
            ]);
        }

        return back()->with('success', "{$approvedCount} student request".($approvedCount === 1 ? '' : 's').' approved successfully.');
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
        return Student::query()
            ->select('id', 'full_name', 'phone')
            ->orderBy('full_name')
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->full_name,
                'phone' => $student->phone,
            ])
            ->all();
    }

    private function presentClassData(StudyClass $studyClass): array
    {
        $config = $this->resolveEnrollConfig($studyClass);
        $resolvedPrice = $config?->resolvedPrice() ?? (float) $studyClass->price;
        $resolvedDocumentPrice = $config !== null ? (float) $config->document_price : (float) $studyClass->document_price;

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
            'price' => $resolvedPrice,
            'document_price' => $resolvedDocumentPrice,
            'attendance_latitude' => $studyClass->attendance_latitude !== null ? (float) $studyClass->attendance_latitude : null,
            'attendance_longitude' => $studyClass->attendance_longitude !== null ? (float) $studyClass->attendance_longitude : null,
            'attendance_radius_meters' => $studyClass->attendance_radius_meters !== null ? (int) $studyClass->attendance_radius_meters : null,
            'enrollment_start_date' => $studyClass->enrollment_start_date?->format('Y-m-d'),
            'start_date' => $studyClass->start_date?->format('Y-m-d'),
            'end_date' => $studyClass->end_date?->format('Y-m-d'),
        ];
    }

    private function resolveEnrollConfig(StudyClass $studyClass): ?\App\Models\CourseEnrollConfig
    {
        $query = \App\Models\CourseEnrollConfig::where('course_id', $studyClass->course_id);

        if ($studyClass->time_id !== null) {
            $query->where('time_id', $studyClass->time_id);
        } else {
            $query->whereNull('time_id');
        }

        return $query->first();
    }

    private function approvePendingEnrollment(StudentEnrollment $enrollment, StudentRegistrationService $registrations): void
    {
        abort_unless($enrollment->enrollment_status === 'pending', 422, 'This request has already been resolved.');
        abort_unless($enrollment->studyClass !== null, 404);

        $this->ensureInstructorOwnsClass($enrollment->studyClass);
        $registrations->ensureClassHasSeat($enrollment->studyClass, 'student_id');

        $enrollment->update([
            'enrollment_status' => 'active',
            'source' => $enrollment->source ?? 'qr_code',
        ]);
    }

    private function ensureClassAcceptsMutations(StudyClass $studyClass): void
    {
        abort_unless(
            ! in_array($this->normaliseClassStatus($studyClass->status), ['pre_end', 'ended', 'cancelled'], true),
            422,
            'This class is no longer accepting student changes.',
        );
    }

    private function normaliseClassStatus(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'inactive' => 'pre_end',
            'completed' => 'ended',
            default => strtolower((string) $status),
        };
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
        if ($this->isSelfManagingInstructor()) {
            abort_unless($studyClass->teacher_id === auth()->id(), 403, 'You can only manage classes assigned to you.');
        }
    }

    /**
     * An instructor acting on their own classes — i.e. not also an admin, who manages every
     * class and works from the admin screens rather than the instructor dashboard.
     */
    private function isSelfManagingInstructor(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->hasRole('instructor')
            && ! $user->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * After saving a class: the admin class list, or the instructor dashboard, which is
     * the only class list an instructor can reach.
     */
    private function redirectToClassList(): RedirectResponse
    {
        return redirect()->route($this->isSelfManagingInstructor() ? 'dashboard' : 'enroll.index');
    }
}
