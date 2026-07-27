<?php

namespace App\Modules\Notification\Controllers;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Modules\Auth\Services\AuthAuditService;
use App\Modules\Notification\Events\NotificationsUpdated;
use App\Modules\User\Services\UserApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays dashboard notifications and returns notification data.
 */
class NotificationController extends Controller
{
    public function __construct(
        private readonly UserApprovalService $approvalService,
        private readonly AuthAuditService $auditService,
    ) {}

    public function showNotificationPage(Request $request): Response
    {
        abort_unless(
            $request->user()?->hasRole('super_admin')
                || $request->user()?->hasRole('admin'),
            403
        );

        return Inertia::render('backend/notifications/Notification');
    }

    public function getNotificationData(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasRole('super_admin')
                || $request->user()?->hasRole('admin'),
            403
        );

        $notifications = Notification::query()
            ->with('otpVerification.user:id,name,email,status')
            ->latest('id')
            ->limit(20)
            ->get(['id', 'title', 'message', 'type', 'otp_verification_id', 'is_read', 'created_at'])
            ->map(fn (Notification $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'is_read' => $notification->is_read,
                'created_at' => $notification->created_at,
                'approval_status' => $this->approvalStatusFor($notification),
            ]);

        return response()->json([
            'unread_count' => Notification::query()->where('is_read', false)->count(),
            'data' => $notifications,
        ]);
    }

    public function approve(Request $request, Notification $notification): JsonResponse
    {
        return $this->resolve($request, $notification, 'approve');
    }

    public function reject(Request $request, Notification $notification): JsonResponse
    {
        return $this->resolve($request, $notification, 'reject');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasRole('super_admin')
                || $request->user()?->hasRole('admin'),
            403
        );

        Notification::query()->where('is_read', false)->update(['is_read' => true]);

        NotificationsUpdated::dispatch();

        return response()->json(['unread_count' => 0]);
    }

    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        abort_unless(
            $request->user()?->hasRole('super_admin')
                || $request->user()?->hasRole('admin'),
            403
        );

        $notification->delete();

        NotificationsUpdated::dispatch();

        return response()->json(['deleted' => true]);
    }

    private function resolve(Request $request, Notification $notification, string $action): JsonResponse
    {
        abort_unless(
            $request->user()?->hasRole('super_admin')
                || $request->user()?->hasRole('admin'),
            403
        );

        $user = $notification->otpVerification?->user;

        if ($notification->type !== 'instructor_approval' || ! $user) {
            throw ValidationException::withMessages([
                'notification' => ['This notification cannot be actioned.'],
            ]);
        }

        if ($user->status === UserStatus::Rejected) {
            throw ValidationException::withMessages([
                'notification' => ['User already rejected.'],
            ]);
        }

        if ($action === 'approve') {
            $this->approvalService->approve($user, $request->user()->id, 'dashboard');
            $this->auditService->log($user, 'dashboard.approved', $request->ip(), [
                'otp_id' => $notification->otp_verification_id,
            ]);
        } else {
            $this->approvalService->reject($user, $request->user()->id, 'dashboard');
            $this->auditService->log($user, 'dashboard.rejected', $request->ip(), [
                'otp_id' => $notification->otp_verification_id,
            ]);
        }

        // UserApprovalService already syncs this notification's is_read flag
        // and dispatches NotificationsUpdated - see its syncDashboardNotification().

        return response()->json([
            'approval_status' => $this->approvalStatusFor($notification->fresh('otpVerification.user')),
        ]);
    }

    private function approvalStatusFor(Notification $notification): ?string
    {
        if ($notification->type !== 'instructor_approval') {
            return null;
        }

        $status = $notification->otpVerification?->user?->status;

        return match ($status) {
            UserStatus::Rejected => 'rejected',
            UserStatus::Active => 'approved',
            default => 'pending',
        };
    }
}
