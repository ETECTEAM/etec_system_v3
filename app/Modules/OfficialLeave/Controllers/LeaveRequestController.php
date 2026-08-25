<?php

namespace App\Modules\OfficialLeave\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequestSession;
use App\Models\OfficialLeave;
use App\Models\Student;
use App\Modules\OfficialLeave\Requests\GenerateLeaveQrRequest;
use App\Modules\OfficialLeave\Requests\StorePublicLeaveRequest;
use App\Modules\OfficialLeave\Services\AuditLogger;
use App\Modules\OfficialLeave\Services\LeavePresenterService;
use App\Modules\OfficialLeave\Services\LeaveQrService;
use App\Modules\OfficialLeave\Services\OfficialLeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The office desk flow: student search, QR generation, live poll of the scanned
 * session, and the public form endpoints behind the signed URL.
 */
class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveQrService $qrService,
        private readonly OfficialLeaveService $leaveService,
        private readonly LeavePresenterService $presenter,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function dashboard(): Response
    {
        return Inertia::render('backend/official-leaves/Dashboard');
    }

    public function searchStudents(Request $request): JsonResponse
    {
        $term = $request->string('search')->toString();

        return response()->json([
            'data' => $this->presenter->searchStudents($term),
        ]);
    }

    public function generateQr(GenerateLeaveQrRequest $request): JsonResponse
    {
        $student = Student::query()->findOrFail($request->validated('student_id'));

        $session = $this->qrService->createSession($student, $request->user());

        $this->auditLogger->log($request->user(), AuditLogger::ACTION_QR_GENERATED, null, null, [
            'session_id' => $session['session_id'],
            'student_id' => $student->id,
        ], $request->ip());

        return response()->json(['data' => $session]);
    }

    /**
     * Polled every ~3s by the QR modal while it waits for the phone submission.
     */
    public function pollSession(Request $request, LeaveRequestSession $session): JsonResponse
    {
        if ($session->isUsed()) {
            // The leave created from this session — this becomes the review card.
            $leave = OfficialLeave::query()
                ->where('leave_request_session_id', $session->id)
                ->first();

            return response()->json([
                'state' => $leave ? 'submitted' : 'used',
                'leave' => $leave ? $this->presenter->presentLeave($leave) : null,
            ]);
        }

        if ($session->isExpired()) {
            return response()->json([
                'state' => 'expired',
                'expires_at' => $session->expires_at->toIso8601String(),
            ]);
        }

        return response()->json([
            'state' => 'waiting',
            'expires_at' => $session->expires_at->toIso8601String(),
            'remaining_seconds' => max(0, now()->diffInSeconds($session->expires_at, false)),
        ]);
    }

    /**
     * Public signed-URL form (route leave.form): shows the request form for a valid,
     * unused token; friendly states otherwise.
     */
    public function showPublicForm(string $token): Response
    {
        $resolved = $this->qrService->resolve($token);
        $session = $resolved['session'];
        $state = $resolved['state'];

        $studentCard = $session && in_array($state, ['valid', 'already_used'], true)
            ? $this->presenter->presentStudentCard($session->student)
            : null;

        return Inertia::render('frontend/LeaveRequestForm', [
            'state' => $state,
            'student' => $studentCard,
            'expiresAt' => $session?->expires_at?->toIso8601String(),
        ]);
    }

    public function storePublicForm(StorePublicLeaveRequest $request, string $token): JsonResponse
    {
        $leave = $this->leaveService->submitFromToken($token, $request->validated());

        return response()->json([
            'message' => 'Your leave request was submitted. The office will review it shortly.',
            'leave_id' => $leave->id,
        ]);
    }
}
