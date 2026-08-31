<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\StudyClass;
use App\Modules\Attendance\Actions\OverrideAttendanceRecord;
use App\Modules\Attendance\Queries\GetSessionBanner;
use App\Modules\Attendance\Services\AttendanceQrService;
use App\Modules\Enroll\Queries\GetClassFormOptions;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;
use App\Modules\Instructor\Services\InstructorClassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use stdClass;

class InstructorClassController extends Controller
{
    public function __construct(
        private readonly InstructorClassService $instructorClasses,
        private readonly OverrideAttendanceRecord $overrideAttendance,
        private readonly GetSessionBanner $sessionBanner,
        private readonly AttendanceQrService $attendanceQr,
    ) {}

    public function create(Request $request): Response
    {
        return Inertia::render(
            'backend/instructors/CreateClass',
            $this->instructorClasses->formOptions((int) $request->user()->id),
        );
    }

    public function store(Request $request, InstructorAssignmentAvailability $availability): RedirectResponse
    {
        $validated = $request->validate([
            // Not asked for on the form - the class title is the course title.
            'title' => ['nullable', 'string', 'max:255'],
            'course_id' => ['required', 'exists:courses,id'],
            'lesson_id' => ['nullable', 'exists:course_lessons,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'time_id' => ['required', 'exists:times,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'class_type_id' => ['nullable', 'exists:class_type,class_type_id'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in(GetClassFormOptions::STATUSES)],
            'attendance_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'attendance_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'attendance_radius_meters' => ['nullable', 'integer', 'min:1', 'max:5000'],
        ]);

        // The form only offers slots the instructor is free for; re-check here so a
        // stale form or a direct POST can't book an overlapping / unavailable slot.
        $reason = $availability->unavailableReason(
            (int) $request->user()->id,
            (int) $validated['term_id'],
            (int) $validated['time_id'],
        );

        if ($reason !== null) {
            throw ValidationException::withMessages(['time_id' => $reason]);
        }

        // Title always mirrors the course title (like SaveStudyClassRequest does
        // for the admin class form).
        $validated['title'] = Course::query()->whereKey($validated['course_id'])->value('title')
            ?? ($validated['title'] ?: 'New Class');

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
        $this->instructorClasses->ensureTodayAttendanceSession($class, $request->user());
        $attendanceWindow = $this->instructorClasses->attendanceWindow($class->id, Carbon::today('Asia/Phnom_Penh'));
        $todaySession = $this->sessionBanner->handle($class->id);

        return Inertia::render('backend/instructors/AttendanceRecord', [
            'classData' => $this->instructorClasses->presentClass($class),
            'students' => $this->instructorClasses->students($class->id),
            'pendingRegistrations' => $this->instructorClasses->pendingRegistrations($class->id),
            'attendanceWindow' => $attendanceWindow,
            'todaySession' => $todaySession,
            'canTrackAttendance' => $this->instructorClasses->canTrackAttendance($class, $attendanceWindow, $todaySession),
            'trackAttendanceLabel' => $this->instructorClasses->trackAttendanceLabel($class, $attendanceWindow, $todaySession),
        ]);
    }

    public function preAttendance(Request $request): Response
    {
        return Inertia::render('backend/instructors/PreAttendance', [
            'classes' => $this->instructorClasses->preAttendanceClasses($request->user()),
        ]);
    }

    public function result(Request $request, string $studyClass): Response|BinaryFileResponse
    {
        $class = $this->instructorClasses->findResultForInstructor($request->user(), (int) $studyClass);
        $students = $this->instructorClasses->students($class->id);

        if ($request->boolean('download', false)) {
            return $this->downloadResultPdf($class, $students);
        }

        return Inertia::render('backend/instructors/ClassResult', [
            'classData' => $this->instructorClasses->presentClass($class),
            'students' => $students,
            'autoDownload' => $request->boolean('download', false),
        ]);
    }

    public function trackAttendance(Request $request, string $studyClass): Response|RedirectResponse
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);
        $studyClassModel = StudyClass::query()->findOrFail($class->id);
        $qrAttendanceAvailable = $this->attendanceQr->allowsQrAttendance($studyClassModel);
        $allowTrackAnytime = $this->attendanceQr->allowsTrackAnytime();

        if (($class->class_status ?? null) !== 'active') {
            return redirect()
                ->route('instructor.classes.attendance', $class->id)
                ->with('warning', 'Attendance can only be tracked while the class is active.');
        }

        $this->instructorClasses->ensureTodayAttendanceSession($class, $request->user());
        $attendanceWindow = $this->instructorClasses->attendanceWindow($class->id, Carbon::today('Asia/Phnom_Penh'));
        $todaySession = $this->sessionBanner->handle($class->id);
        $hasAttendance = $this->instructorClasses->hasAttendanceForDate($class->id, Carbon::today('Asia/Phnom_Penh'));
        $canCompletePreAttendance = (bool) ($todaySession['is_pre_attendance'] ?? false);
        $isAutoRecorded = ($todaySession['status'] ?? null) === 'auto_recorded';
        $canOpenAttendance = ! $isAutoRecorded && ($allowTrackAnytime || $canCompletePreAttendance || (bool) ($attendanceWindow['can_submit'] ?? false));
        $attendanceSession = $qrAttendanceAvailable && $canOpenAttendance
            ? $this->attendanceQr->getOrCreateTodaySession($studyClassModel, $request->user())
            : AttendanceSession::query()
                ->where('study_class_id', $class->id)
                ->whereDate('attendance_date', Carbon::today('Asia/Phnom_Penh'))
                ->first();
        $presentedAttendanceSession = $attendanceSession
            ? $this->attendanceQr->presentSession($attendanceSession, $studyClassModel)
            : null;
        $attendanceSummary = $attendanceSession
            ? $this->attendanceQr->teacherSummary($attendanceSession, $studyClassModel)
            : null;
        $canCorrectQrAttendance = ($presentedAttendanceSession['status'] ?? null) === AttendanceSession::STATUS_ACTIVE;

        return Inertia::render('backend/instructors/TrackAttendance', [
            'classData' => $this->instructorClasses->presentClass($class),
            'students' => $this->instructorClasses->students($class->id),
            'attendanceLocked' => $isAutoRecorded || (! $canCorrectQrAttendance
                && ! $canCompletePreAttendance
                && ($hasAttendance
                    || ! ($attendanceWindow['can_submit'] ?? false)
                    || ($presentedAttendanceSession['status'] ?? null) === AttendanceSession::STATUS_STOPPED)),
            'attendanceWindow' => $attendanceWindow,
            'todaySession' => $todaySession,
            'attendanceSession' => $presentedAttendanceSession,
            'attendanceSummary' => $attendanceSummary,
            'qrAttendanceAvailable' => $qrAttendanceAvailable,
            'allowTrackAnytime' => $allowTrackAnytime,
        ]);
    }

    public function startAttendanceSession(Request $request, string $studyClass): RedirectResponse
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);
        $studyClassModel = StudyClass::query()->findOrFail($class->id);

        if (($class->class_status ?? null) !== 'active') {
            return back()->with('warning', 'Attendance can only be tracked while the class is active.');
        }

        $this->attendanceQr->startSession($studyClassModel, $request->user());

        return back()->with('success', 'Attendance session started successfully.');
    }

    public function stopAttendanceSession(Request $request, string $studyClass): RedirectResponse
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);
        $studyClassModel = StudyClass::query()->findOrFail($class->id);
        $session = AttendanceSession::query()
            ->where('study_class_id', $studyClassModel->id)
            ->whereDate('attendance_date', Carbon::today('Asia/Phnom_Penh'))
            ->first();

        if (! $session) {
            return back()->with('warning', 'No attendance session is available to stop.');
        }

        $this->attendanceQr->stopSession($session, $request->user());

        return back()->with('success', 'Attendance session stopped successfully.');
    }

    public function studentAttendance(Request $request, string $studyClass, string $student): Response
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        return Inertia::render('backend/instructors/StudentAttendanceDetail', [
            'classData' => $this->instructorClasses->presentClass($class),
            'student' => $this->instructorClasses->studentAttendanceDetail($class->id, (int) $student),
        ]);
    }

    public function groups(Request $request, string $studyClass): Response
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        return Inertia::render('backend/instructors/ClassGroups', [
            'classData' => $this->instructorClasses->presentClass($class),
            'students' => $this->instructorClasses->students($class->id),
            'savedTeams' => $this->instructorClasses->teamsForClass($class->id),
        ]);
    }

    private function downloadResultPdf(stdClass $class, Collection $students): BinaryFileResponse
    {
        $classData = $this->instructorClasses->presentClass($class);
        $sortedStudents = $students
            ->sort(function (array $left, array $right): int {
                $bucketDifference = $this->resultSortBucket($left) <=> $this->resultSortBucket($right);

                if ($bucketDifference !== 0) {
                    return $bucketDifference;
                }

                return $this->resultTotalScore($right) <=> $this->resultTotalScore($left);
            })
            ->values();

        $pdfPath = sys_get_temp_dir() . '/class-result-' . Str::uuid() . '.pdf';
        $htmlPath = sys_get_temp_dir() . '/class-result-' . Str::uuid() . '.html';

        File::put($htmlPath, view('backend.instructors.class-result-pdf', [
            'classData' => $classData,
            'students' => $sortedStudents,
        ])->render());

        $process = Process::timeout(120)->run([
            '/usr/bin/google-chrome',
            '--headless',
            '--disable-gpu',
            '--no-sandbox',
            '--no-pdf-header-footer',
            '--hide-scrollbars',
            '--run-all-compositor-stages-before-draw',
            '--virtual-time-budget=2000',
            '--print-to-pdf=' . $pdfPath,
            '--print-to-pdf-no-header',
            'file://' . $htmlPath,
        ]);

        File::delete($htmlPath);

        if (! $process->successful() || ! File::exists($pdfPath)) {
            if (File::exists($pdfPath)) {
                File::delete($pdfPath);
            }

            abort(500, 'Unable to generate the class result PDF.');
        }

        return response()
            ->download($pdfPath, Str::slug($classData['title'] ?? 'class-result') . '.pdf', [
                'Content-Type' => 'application/pdf',
            ])
            ->deleteFileAfterSend(true);
    }

    private function resultSortBucket(array $student): int
    {
        return $this->resultTotalScore($student) < 50 ? 1 : 0;
    }

    private function resultTotalScore(array $student): float
    {
        return (float) ($student['scores']['attendance'] ?? 0)
            + (float) ($student['scores']['activity'] ?? 0)
            + (float) ($student['scores']['exam'] ?? 0);
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

    public function saveTeams(Request $request, string $studyClass): JsonResponse
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);
        $studentCount = $this->instructorClasses->students($class->id)->count();

        if ($studentCount === 0) {
            return response()->json([
                'message' => 'No students are enrolled in this class yet.',
            ], 422);
        }

        $validated = $request->validate([
            'teams_count' => ['required', 'integer', 'min:1', 'max:'.$studentCount],
            'teams' => ['required', 'array', 'min:1'],
            'teams.*.team_name' => ['required', 'string', 'max:255'],
            'teams.*.project_topic' => ['nullable', 'string', 'max:255'],
            'teams.*.student_ids' => ['required', 'array', 'min:1'],
            'teams.*.student_ids.*' => ['required', 'integer', 'exists:students,id'],
        ]);

        $savedTeams = $this->instructorClasses->saveTeams(
            $class->id,
            (int) $validated['teams_count'],
            $validated['teams'],
            $request->user()?->id,
        );

        return response()->json([
            'message' => 'Teams saved successfully.',
            'teams' => $savedTeams,
        ]);
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

        if (($class->class_status ?? null) !== 'active') {
            return redirect()
                ->route('instructor.classes.attendance', $class->id)
                ->with('warning', 'Attendance can only be tracked while the class is active.');
        }

        $validated = $request->validate([
            'attendance_date' => ['nullable', 'date'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'records.*.enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'records.*.status' => ['required', 'string', Rule::in(InstructorClassService::ATTENDANCE_STATUSES)],
            'records.*.note' => ['nullable', 'string', 'max:255'],
            'stop_session' => ['nullable', 'boolean'],
        ]);

        $this->instructorClasses->saveAttendance($request->user(), $class->id, $validated);

        return redirect()
            ->route('instructor.classes.attendance', $class->id)
            ->with('success', 'Attendance saved successfully.');
    }

    public function overrideAttendance(Request $request, string $studyClass): RedirectResponse
    {
        $class = $this->instructorClasses->findForInstructor($request->user(), (int) $studyClass);

        if (($class->class_status ?? null) !== 'active') {
            return redirect()
                ->route('instructor.classes.attendance', $class->id)
                ->with('warning', 'Attendance can only be tracked while the class is active.');
        }

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
