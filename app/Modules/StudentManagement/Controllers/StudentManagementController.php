<?php

namespace App\Modules\StudentManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudentPermission;
use App\Models\StudyClass;
use App\Models\Time;
use App\Modules\Enroll\Actions\MoveStudentEnrollment;
use App\Modules\Instructor\Services\InstructorClassService;
use App\Modules\StudentManagement\Requests\GrantPermissionRequest;
use App\Modules\StudentManagement\Requests\TransferStudentRequest;
use App\Modules\StudentManagement\Requests\UpdateStudentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentManagementController extends Controller
{
    public function attendance(StudentEnrollment $enrollment, InstructorClassService $instructorClasses): Response
    {
        $enrollment->load(['studyClass.course:id,title', 'studyClass.time:id,time_name']);
        $studyClass = $enrollment->studyClass;

        abort_unless($studyClass, 404);

        return Inertia::render('backend/instructors/StudentAttendanceDetail', [
            'classData' => [
                'id' => $studyClass->id,
                'title' => $studyClass->title,
                'term' => '-',
                'time' => $studyClass->time?->time_name ?? '-',
            ],
            'backUrl' => '/dashboard/student-management',
            'student' => $instructorClasses->studentAttendanceDetail($studyClass->id, $enrollment->student_id),
        ]);
    }

    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $today = Carbon::today('Asia/Phnom_Penh');
        $enrollments = StudentEnrollment::query()
            ->with([
                'student:id,full_name,gender,phone',
                'studyClass.course:id,title',
                'studyClass.teacher:id,name',
                'studyClass.time:id,time_name',
                'attendances' => fn ($query) => $query->whereDate('attendance_date', $today),
            ])
            ->where('enrollment_status', 'active')
            ->when($search !== '', fn ($q) => $q->whereHas('student', fn ($s) => $s->where('full_name', 'like', "%{$search}%")))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->integer('course_id')))
            ->when($request->filled('time_id'), fn ($q) => $q->where('time_id', $request->integer('time_id')))
            ->latest('id')->paginate(10)->withQueryString();

        $sessions = ClassSession::query()
            ->whereIn('study_class_id', $enrollments->getCollection()->pluck('study_class_id')->filter()->unique())
            ->whereDate('session_date', $today)
            ->get()
            ->keyBy('study_class_id');

        return Inertia::render('backend/student-management/Index', [
            'enrollments' => $enrollments->through(fn (StudentEnrollment $e) => $this->present($e, $sessions->get($e->study_class_id), $today)),
            'filters' => ['search' => $search, 'course_id' => $request->integer('course_id') ?: null, 'time_id' => $request->integer('time_id') ?: null],
            'courses' => Course::query()->select('id', 'title')->orderBy('title')->get(),
            'times' => Time::query()->select('id', 'time_name')->orderBy('time_name')->get(),
            'classes' => StudyClass::query()->with(['course:id,title', 'teacher:id,name', 'time:id,time_name'])->whereIn('status', StudyClass::LIVE_STATUSES)->orderBy('title')->get(['id', 'title', 'course_id', 'teacher_id', 'time_id', 'capacity']),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());

        return back()->with('success', 'Student updated successfully.');
    }

    public function permission(GrantPermissionRequest $request, Student $student): RedirectResponse
    {
        StudentPermission::create([...$request->validated(), 'student_id' => $student->id, 'approved_by' => $request->user()->id]);

        return back()->with('success', 'Permission added successfully.');
    }

    public function transfer(TransferStudentRequest $request, StudentEnrollment $enrollment, MoveStudentEnrollment $move): RedirectResponse
    {
        $move->handle($enrollment->load('student'), StudyClass::findOrFail($request->integer('study_class_id')), $request->boolean('force'));

        return back()->with('success', 'Student transferred successfully.');
    }

    public function late(Request $request, StudentEnrollment $enrollment): RedirectResponse
    {
        $today = Carbon::today('Asia/Phnom_Penh');
        $enrollment->load('studyClass');

        if ($enrollment->enrollment_status !== 'active' || ! $enrollment->studyClass) {
            throw ValidationException::withMessages(['late' => 'This student is not actively enrolled in a class.']);
        }

        DB::transaction(function () use ($request, $enrollment, $today): void {
            $session = ClassSession::query()
                ->where('study_class_id', $enrollment->study_class_id)
                ->whereDate('session_date', $today)
                ->lockForUpdate()
                ->first();

            if (! $session || Carbon::now('Asia/Phnom_Penh')->lt($session->scheduled_start) || Carbon::now('Asia/Phnom_Penh')->gt($session->scheduled_end)) {
                throw ValidationException::withMessages(['late' => 'Late can only be marked during the scheduled class time.']);
            }

            $attendance = StudentAttendance::query()
                ->where('study_class_id', $enrollment->study_class_id)
                ->where('student_enrollment_id', $enrollment->id)
                ->whereDate('attendance_date', $today)
                ->lockForUpdate()
                ->first();

            if ($attendance?->late) {
                throw ValidationException::withMessages(['late' => 'This student has already been marked late today.']);
            }

            if ($attendance) {
                throw ValidationException::withMessages(['late' => 'Attendance has already been recorded for this student today.']);
            }

            StudentAttendance::create([
                'study_class_id' => $enrollment->study_class_id,
                'student_enrollment_id' => $enrollment->id,
                'attendance_date' => $today->toDateString(),
                'student_id' => $enrollment->student_id,
                'tracked_by' => $request->user()->id,
                ...StudentAttendance::flagsFor(StudentAttendance::STATUS_LATE),
                'source' => StudentAttendance::SOURCE_ADMIN_EDIT,
            ]);
        });

        return back()->with('success', 'Student marked late.');
    }

    public function destroy(StudentEnrollment $enrollment): RedirectResponse
    {
        $enrollment->update(['enrollment_status' => 'cancelled']);

        return back()->with('success', 'Student removed from the class.');
    }

    private function present(StudentEnrollment $e, ?ClassSession $session, Carbon $now): array
    {
        $attendance = $e->attendances->first();
        $reason = match (true) {
            (bool) $attendance?->late => 'already_marked',
            $attendance !== null => 'attendance_recorded',
            ! $session => 'no_session',
            $now->lt($session->scheduled_start) => 'before_class',
            $now->gt($session->scheduled_end) => 'after_class',
            default => null,
        };

        return ['enrollment_id' => $e->id, 'student_id' => $e->student_id, 'study_class_id' => $e->study_class_id, 'name' => $e->student?->full_name ?? '-', 'gender' => $e->student?->gender ?? '-', 'phone' => $e->student?->phone ?? '-', 'course' => $e->studyClass?->course?->title ?? $e->course?->title ?? '-', 'class' => $e->studyClass?->title ?? '-', 'instructor' => $e->studyClass?->teacher?->name ?? '-', 'time' => $e->studyClass?->time?->time_name ?? '-', 'created' => optional($e->created_at)->format('M d, Y'), 'late_action' => ['enabled' => $reason === null, 'reason' => $reason]];
    }
}
