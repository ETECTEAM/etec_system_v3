<?php

namespace App\Modules\OfficialLeave\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LeaveRequestSession;
use App\Models\OfficialLeave;
use App\Models\StudyClass;
use App\Modules\OfficialLeave\Requests\StoreLeaveRequest;
use App\Modules\OfficialLeave\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveRequestService
    ) {}

    public function dashboard(): Response
    {
        $this->authorize('viewAny', OfficialLeave::class);

        return Inertia::render('backend/official-leaves/Dashboard');
    }

    public function searchStudents(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OfficialLeave::class);

        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $students = $this->leaveRequestService->searchStudents($request->q);

        return response()->json($students);
    }

    public function generateQr(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OfficialLeave::class);

        $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        $result = $this->leaveRequestService->generateQrToken(
            $request->student_id,
            $request->user()->id
        );

        return response()->json([
            'session_id' => $result['session']->id,
            'url' => $result['url'],
            'expires_at' => $result['expires_at'],
            'token' => $result['token'],
        ]);
    }

    public function pollSession(LeaveRequestSession $session): JsonResponse
    {
        $this->authorize('viewAny', OfficialLeave::class);

        $leave = OfficialLeave::where('leave_request_session_id', $session->id)
            ->where('status', OfficialLeave::STATUS_PENDING)
            ->first();

        return response()->json([
            'leave' => $leave,
        ]);
    }

    public function form(string $token): Response|\Illuminate\Http\RedirectResponse
    {
        $session = $this->leaveRequestService->validateToken($token);

        if (! $session || $session->isExpired() || $session->isUsed()) {
            return redirect('/')->withErrors(['token' => 'Invalid or expired QR token.']);
        }

        $student = $this->leaveRequestService->getStudent($session->student_id);

        return Inertia::render('backend/official-leaves/StudentForm', [
            'student' => $student,
            'token' => $token,
            'expiresAt' => $session->expires_at->toIso8601String(),
        ]);
    }

    public function store(StoreLeaveRequest $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validated();
        $token = $request->query('token');

        if ($token) {
            $session = $this->leaveRequestService->validateToken($token);
            if (! $session || $session->isExpired() || $session->isUsed()) {
                return back()->withErrors(['token' => 'Invalid or expired QR token.']);
            }
            $this->leaveRequestService->consumeToken($session);
            $data['student_id'] = $session->student_id;
            $data['leave_request_session_id'] = $session->id;
        } else {
            // Office-created record — no session link
        }

        OfficialLeave::create($data);

        return redirect('/')->with('success', 'Leave request submitted successfully.');
    }
}
