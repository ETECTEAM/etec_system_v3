<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Actions\OverrideAttendanceRecord;
use App\Modules\Attendance\Queries\GetSessionBanner;
use App\Modules\Enroll\Queries\GetClassFormOptions;
use App\Models\StudyClass;
use App\Modules\Instructor\Services\InstructorClassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InstructorClassController extends Controller
{
    public function __construct(
        private readonly InstructorClassService $instructorClasses,
        private readonly OverrideAttendanceRecord $overrideAttendance,
        private readonly GetSessionBanner $sessionBanner,
    ) {}

    public function create(): Response
    {
        return Inertia::render('backend/instructors/CreateClass', $this->instructorClasses->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'course_id'     => ['required', 'exists:courses,id'],
            'lesson_id'     => ['nullable', 'exists:course_lessons,id'],
            'term_id'       => ['nullable', 'exists:terms,id'],
            'time_id'       => ['nullable', 'exists:times,id'],
            'room_id'       => ['nullable', 'exists:rooms,id'],
            'class_type_id' => ['nullable', 'exists:class_type,class_type_id'],
            'capacity'      => ['nullable', 'integer', 'min:0'],
            'status'        => ['nullable', 'string', Rule::in(GetClassFormOptions::STATUSES)],
        ]);

        $this->instructorClasses->createClass($request->user(), $validated);

        return redirect()->route('dashboard')->with('success', 'Class created successfully.');
    }

    public function show(Request $request, string $studyClass): RedirectResponse
    {
        $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        return redirect()->route('instructor.classes.attendance', $studyClass);
    }

    public function attendance(Request $request, string $studyClass): Response
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        return Inertia::render('backend/instructors/AttendanceRecord', [
            'classData' => $this->instructorClasses->presentClass($class),
            'students' => $this->instructorClasses->students($class->id),
            'todaySession' => $this->sessionBanner->handle($class->id),
        ]);
    }

    public function trackAttendance(Request $request, string $studyClass): Response
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        return Inertia::render('backend/instructors/TrackAttendance', [
            'classData' => $this->instructorClasses->presentClass($class),
            'students' => $this->instructorClasses->students($class->id),
            'attendanceLocked' => $this->instructorClasses->hasAttendanceForDate($class->id, today()),
            'todaySession' => $this->sessionBanner->handle($class->id),
        ]);
    }

    public function studentAttendance(Request $request, string $studyClass, string $student): Response
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        return Inertia::render('backend/instructors/StudentAttendanceDetail', [
            'classData' => $this->instructorClasses->presentClass($class),
            'student' => $this->instructorClasses->studentAttendanceDetail($class->id, (int) $student),
        ]);
    }

    public function saveScores(Request $request, string $studyClass): JsonResponse|RedirectResponse
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        $validated = $request->validate([
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'scores.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'scores.*.attendance_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.activity_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.exam_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $records = collect($validated['scores'])
            ->map(function (array $record): array {
                return [
                    'enrollment_id' => (int) $record['enrollment_id'],
                    'student_id' => (int) $record['student_id'],
                    'attendance_score' => (float) ($record['attendance_score'] ?? 0),
                    'activity_score' => (float) ($record['activity_score'] ?? 0),
                    'exam_score' => (float) ($record['exam_score'] ?? 0),
                ];
            })
            ->all();

        $this->instructorClasses->saveScores($class->id, $records);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Scores saved successfully.',
            ]);
        }

        return back()->with('success', 'Scores saved successfully.');
    }

    public function updateStudent(Request $request, string $studyClass, string $student): JsonResponse|RedirectResponse
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'date_of_birth' => ['nullable', 'date'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $this->instructorClasses->updateStudentProfile($class->id, (int) $student, $validated);

        $request->session()->forget(['error', 'warning', 'info', 'retryAfter', 'isHardBlock']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Student information updated successfully.',
            ]);
        }

        return back()->with('success', 'Student information updated successfully.');
    }

    public function transferStudent(Request $request, string $studyClass, string $student): JsonResponse|RedirectResponse
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        $validated = $request->validate([
            'study_class_id' => ['required', 'integer', 'exists:study_classes,id'],
        ]);

        $targetClass = StudyClass::query()->findOrFail((int) $validated['study_class_id']);

        $this->instructorClasses->transferStudent($class->id, (int) $student, $targetClass);

        $request->session()->forget(['error', 'warning', 'info', 'retryAfter', 'isHardBlock']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Student transferred successfully.',
            ]);
        }

        return back()->with('success', 'Student transferred successfully.');
    }

    public function storeAttendance(Request $request, string $studyClass): RedirectResponse
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        $validated = $request->validate([
            'attendance_date' => ['nullable', 'date'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'records.*.enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'records.*.status' => ['required', 'string', Rule::in(InstructorClassService::ATTENDANCE_STATUSES)],
            'records.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->instructorClasses->saveAttendance($request->user(), $class->id, $validated);

        return redirect()
            ->route('instructor.classes.attendance', $class->id)
            ->with('success', 'Attendance saved successfully.');
    }

    public function overrideAttendance(Request $request, string $studyClass): RedirectResponse
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        $validated = $request->validate([
            'attendance_date' => ['required', 'date'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'records.*.enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'records.*.status' => ['required', 'string', Rule::in(InstructorClassService::ATTENDANCE_STATUSES)],
            'records.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->overrideAttendance->handle(
            $request->user(),
            $class->id,
            $validated['attendance_date'],
            $validated['records'],
        );

        return redirect()
            ->route('instructor.classes.attendance', $class->id)
            ->with('success', 'Attendance correction saved successfully.');
    }
}
