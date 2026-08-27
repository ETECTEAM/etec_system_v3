<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Services\AttendanceQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceQrController extends Controller
{
    public function __construct(private readonly AttendanceQrService $attendanceQr) {}

    public function show(string $token): Response
    {
        return Inertia::render('frontend/attendance/Scan', $this->attendanceQr->publicViewData($token));
    }

    public function store(Request $request, string $token): JsonResponse|RedirectResponse
    {
        $session = $this->attendanceQr->resolveSession($token);

        if (! $session) {
            return $this->invalidResponse($request, 'Invalid or expired attendance QR code.');
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'min:0'],
            'user_agent' => ['nullable', 'string', 'max:1000'],
            'device_identifier' => ['required', 'string', 'max:255'],
        ]);

        $attendance = $this->attendanceQr->recordAttendance($request, $session, $validated);

        $payload = [
            'message' => 'Attendance recorded successfully.',
            'attendance' => [
                'student_id' => $attendance->student_id,
                'class_id' => $attendance->study_class_id,
                'date' => $attendance->attendance_date->format('Y-m-d'),
                'time' => $attendance->created_at?->format('H:i'),
                'status' => $attendance->status,
                'verification_status' => $attendance->verification_status,
                'verification_reason' => $attendance->verification_reason,
            ],
        ];

        return $request->expectsJson()
            ? response()->json($payload)
            : redirect()->back()->with('success', $payload['message']);
    }

    private function invalidResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
