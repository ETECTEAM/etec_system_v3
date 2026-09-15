<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Modules\Enroll\Services\StudentRegistrationService;
use App\Modules\Website\Notifications\StudentAttendanceCodeReminderNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentPortalController extends Controller
{
    public function login(Request $request): Response|RedirectResponse
    {
        if ($request->session()->has('student_portal_id')) {
            return redirect()->route('frontend.student-portal.dashboard');
        }

        return Inertia::render('frontend/student-portal/Login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $validated = $request->validate(['attendance_code' => ['required', 'string', 'size:9']]);
        $code = strtoupper(trim($validated['attendance_code']));
        $key = 'student-portal:'.$request->ip().'|'.$code;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['attendance_code' => 'Too many attempts. Please try again later.']);
        }

        RateLimiter::hit($key, 60);
        $student = Student::query()->where('attendance_code', $code)->first();

        if (! $student) {
            throw ValidationException::withMessages(['attendance_code' => 'Invalid student code.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('student_portal_id', $student->id);

        return redirect()->route('frontend.student-portal.dashboard');
    }

    public function showForgotCode(Request $request): Response
    {
        return Inertia::render('frontend/student-portal/ForgotCode', [
            'returnTo' => $this->attendanceRecoveryReturnUrl($request->query('return_to')),
        ]);
    }

    public function sendForgotCode(Request $request, StudentRegistrationService $registrations): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'regex:/^[^@\\s]+@etec\\.com$/i'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ], [
            'email.regex' => 'Use your Student Portal email ending in @etec.com.',
        ]);
        $student = Student::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])->first();
        $returnTo = $this->attendanceRecoveryReturnUrl($data['return_to'] ?? null);

        if ($student && ! $student->recovery_email) {
            $request->session()->put('student_portal_recovery_setup_id', $student->id);
            $request->session()->put('student_portal_recovery_return_to', $returnTo);

            return redirect()->route('frontend.student-portal.recovery-email.create');
        }

        if ($student?->recovery_email) {
            $newCode = $registrations->newAttendanceCode();

            Notification::route('mail', $student->recovery_email)
                ->notify(new StudentAttendanceCodeReminderNotification($newCode));
            $registrations->replaceAttendanceCode($student, $newCode);
        }

        return redirect()->to($returnTo)
            ->with('success', 'If that Student Portal email is registered, a new code has been sent to its recovery email.');
    }

    public function showRecoveryEmailSetup(Request $request): Response|RedirectResponse
    {
        $student = Student::query()->find($request->session()->get('student_portal_recovery_setup_id'));

        if (! $student || $student->recovery_email) {
            return redirect()->route('frontend.student-portal.forgot-code');
        }

        return Inertia::render('frontend/student-portal/RecoveryEmailSetup', [
            'portalEmail' => $student->email,
        ]);
    }

    public function storeRecoveryEmailSetup(Request $request, StudentRegistrationService $registrations): RedirectResponse
    {
        $student = Student::query()->find($request->session()->get('student_portal_recovery_setup_id'));

        if (! $student || $student->recovery_email) {
            return redirect()->route('frontend.student-portal.forgot-code');
        }

        $data = $request->validate(['recovery_email' => ['required', 'email', 'max:255']]);
        $registrations->setRecoveryEmail($student, $data['recovery_email']);

        $newCode = $registrations->newAttendanceCode();
        Notification::route('mail', $student->recovery_email)
            ->notify(new StudentAttendanceCodeReminderNotification($newCode));
        $registrations->replaceAttendanceCode($student, $newCode);

        $request->session()->forget('student_portal_recovery_setup_id');
        $returnTo = $request->session()->pull('student_portal_recovery_return_to', '/student-portal/login');

        return redirect()->to($returnTo)
            ->with('success', 'Your recovery email has been saved and a new code has been sent.');
    }

    private function attendanceRecoveryReturnUrl(mixed $returnTo): string
    {
        if (is_string($returnTo) && str_starts_with($returnTo, '/attendance/qr/')) {
            return $returnTo;
        }

        return '/student-portal/login';
    }

    public function dashboard(Request $request): Response
    {
        $student = Student::query()->with([
            'enrollments.studyClass.course',
            'enrollments.studyClass.teacher',
            'enrollments.studyClass.term',
            'enrollments.studyClass.time',
            'enrollments.studyClass.room',
            'enrollments.attendances',
        ])->findOrFail((int) $request->session()->get('student_portal_id'));

        return Inertia::render('frontend/student-portal/Dashboard', [
            'student' => [
                'name' => $student->full_name,
                'gender' => $student->gender,
                'phone' => $student->phone,
                'email' => $student->email,
                'status' => $student->student_status,
            ],
            'enrollments' => $student->enrollments->map(function ($enrollment): array {
                $class = $enrollment->studyClass;
                $attendances = $enrollment->attendances;

                return [
                    'id' => $enrollment->id,
                    'status' => $enrollment->enrollment_status,
                    'class_title' => $class?->title ?? $class?->course?->title ?? 'Class',
                    'instructor' => $class?->teacher?->name ?? '-',
                    'schedule' => trim(($class?->term?->term_name ?? '').' '.($class?->time?->time_name ?? '')) ?: '-',
                    'room' => $class?->room?->room_number ?? '-',
                    'present_count' => $attendances->filter(fn ($row) => $row->present)->count(),
                    'absent_count' => $attendances->filter(fn ($row) => $row->absent)->count(),
                    'permission_count' => $attendances->filter(fn ($row) => $row->permission)->count(),
                    'late_count' => $attendances->filter(fn ($row) => $row->late)->count(),
                    'last_check_in' => $attendances->sortByDesc('created_at')->first()?->created_at?->format('Y-m-d H:i'),
                    'attendance' => $attendances->sortByDesc('attendance_date')->values()->map(fn ($row): array => [
                        'date' => $row->attendance_date?->format('M j, Y (D)'),
                        'status' => $row->statusLabel(),
                        'time' => $row->created_at?->format('g:i A'),
                    ])->values()->all(),
                ];
            })->values()->all(),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('student_portal_id');
        $request->session()->regenerateToken();

        return redirect()->route('frontend.student-portal.login');
    }

}
