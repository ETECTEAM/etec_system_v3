<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Enroll\Queries\GetClassFormOptions;
use App\Modules\Instructor\Services\InstructorClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InstructorClassController extends Controller
{
    public function __construct(private readonly InstructorClassService $instructorClasses) {}

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
        ]);
    }

    public function trackAttendance(Request $request, string $studyClass): Response
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        return Inertia::render('backend/instructors/TrackAttendance', [
            'classData' => $this->instructorClasses->presentClass($class),
            'students' => $this->instructorClasses->students($class->id),
            'attendanceLocked' => $this->instructorClasses->hasAttendanceForDate($class->id, today()),
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
}
