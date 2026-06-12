<?php

use App\Http\Middleware\RedirectAdminFromFrontend;
use App\Modules\Auth\Controllers\PermissionController;
use App\Modules\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
/*
|--------------------------------------------------------------------------
| Backend Auth Routes
|--------------------------------------------------------------------------
| Login, register, logout, code verify, etc.
*/

Route::group([], function (): void {
    includeRouteFiles(__DIR__.'/web/backend');
});

// Root redirect
Route::get('/', function () {
    return redirect('/admin/courses');
});
/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Admin dashboard only
    |--------------------------------------------------------------------------
    */

 Route::get('/dashboard', function () {
    $user = Auth::user();

    if ($user->hasRole('admin')) {
        return inertia('backend/Home');
    }

    if ($user->hasRole('instructor')) {
        return redirect()->route('dashboard.users.index');
    }

    abort(403);
})->name('dashboard');
    /*
    |--------------------------------------------------------------------------
    | Admin only routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/dashboard/users/roles', function () {
            return inertia('backend/users/Roles');
        })->name('dashboard.users.roles');

        Route::get('/dashboard/users/permissions', function () {
            return inertia('backend/users/Permissions');
        })->name('dashboard.users.permissions');

        Route::get('/dashboard/users/{user}/edit', [UserController::class, 'edit'])
            ->name('dashboard.users.edit');

        Route::put('/dashboard/users/{user}', [UserController::class, 'update'])
            ->name('dashboard.users.update');

        Route::patch('/dashboard/users/{user}', [UserController::class, 'update'])
            ->name('dashboard.users.update.patch');

        Route::delete('/dashboard/users/{user}', [UserController::class, 'destroy'])
            ->name('dashboard.users.destroy');

        Route::prefix('dashboard/permissions')
            ->name('dashboard.permissions.')
            ->group(function () {
                Route::get('/roles', [PermissionController::class, 'roles'])
                    ->name('roles');

                Route::post('/roles', [PermissionController::class, 'createRole'])
                    ->name('roles.store');

                Route::post('/permissions', [PermissionController::class, 'createPermission'])
                    ->name('permissions.store');

                Route::post('/features', [PermissionController::class, 'createFeaturePermissions'])
                    ->name('features.store');

                Route::post('/roles/assign-permissions', [PermissionController::class, 'assignPermissionsToRole'])
                    ->name('roles.assign-permissions');

                Route::post('/users/{user}/assign-role', [PermissionController::class, 'assignRoleToUser'])
                    ->name('users.assign-role');

                Route::post('/users/{user}/assign-permissions', [PermissionController::class, 'assignPermissionsToUser'])
                    ->name('users.assign-permissions');
            });
    });

    /*
    |--------------------------------------------------------------------------
    | Admin + Instructor can view instructor list/detail
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:admin|instructor'])->group(function () {
        Route::get('/dashboard/users', [UserController::class, 'index'])
            ->name('dashboard.users.index');

        Route::get('/dashboard/users/{user}', [UserController::class, 'show'])
            ->name('dashboard.users.show');
    });
});

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
*/

<<<<<<< HEAD
// Route::middleware(RedirectAdminFromFrontend::class)->group(function (): void {
//     includeRouteFiles(__DIR__.'/web/frontend');
// });
=======
Route::middleware(RedirectAdminFromFrontend::class)->group(function (): void {
    includeRouteFiles(__DIR__.'/web/frontend');
});
>>>>>>> 8c762159b54856bc87fbd12c230f63929af3c175
