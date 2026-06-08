<?php

namespace App\Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Displays dashboard notifications and returns notification data.
 */
class NotificationController extends Controller
{
    public function showNotificationPage(Request $request): Response
    {
        abort_unless(
            $request->user()?->hasRole('super_admin')
                || $request->user()?->hasRole('admin'),
            403
        );

        return Inertia::render('backend/notifications/Index');
    }

    public function getNotificationData(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasRole('super_admin')
                || $request->user()?->hasRole('admin'),
            403
        );

        $notifications = Notification::query()
            ->latest('id')
            ->limit(20)
            ->get(['id', 'title', 'message', 'is_read', 'created_at']);

        return response()->json([
            'unread_count' => Notification::query()->where('is_read', false)->count(),
            'data' => $notifications,
        ]);
    }
}
