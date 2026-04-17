<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasRole('super_admin') || $request->user()?->hasRole('admin'),
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
