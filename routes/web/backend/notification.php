<?php


/*
|--------------------------------------------------------------------------
| Backend Notification Routes
|--------------------------------------------------------------------------
|
| These routes handle the web-based notification flow for the backend
| interface, including viewing notifications and fetching notification data.
| Authenticated users with appropriate roles can access these routes.
|
*/

// Import necessary classes for route definitions and controller handling.
use App\Http\Controllers\Admin\NotificationController;

// Group routes that require authentication and specific roles (super_admin or admin).
use Illuminate\Support\Facades\Route;

// Define routes for notifications, protected by authentication and role-based access control.
Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
    // Route to display the notifications page in the dashboard.
    Route::get('/dashboard/notifications', [NotificationController::class, 'showNotificationPage'])
        ->name('dashboard.notifications.index');

    // Route to fetch notification data for the authenticated user.
    Route::get('/notifications/data', [NotificationController::class, 'getNotificationData'])
        ->name('notifications.data');
});
