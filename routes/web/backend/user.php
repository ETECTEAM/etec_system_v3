<?php


/*
|--------------------------------------------------------------------------
| Backend Users Routes
|--------------------------------------------------------------------------
|
| These routes handle the web-based user management flow for the backend
| interface, including creating, viewing, editing, and deleting users.
| Authenticated users with appropriate roles can access these routes.
|
|
*/

// Import necessary classes for route definitions and controller handling.
use App\Modules\User\Controllers\UserManagementController;

// Group routes that require authentication for user management actions.
use App\Modules\User\Controllers\UserController;

// Define routes for user management, protected by authentication middleware.
use Illuminate\Support\Facades\Route;

// Group routes under the '/dashboard/users' prefix for user management operations.
Route::middleware('auth')->prefix('/dashboard/users')->group(function () {

    // Reads: generous limit, just enough to stop scripted scraping/polling abuse.
    Route::middleware('throttle:60,1')->group(function () {
        // Route to display the list of users in the dashboard.
        Route::get('/', [UserController::class, 'index']);

        // Route to fetch paginated user data for the authenticated user.
        Route::get('/data', [UserController::class, 'paginatedIndex']);

        // Route to display the user creation form.
        Route::get('/create', [UserController::class, 'create']);

        // Route to display the user editing form for a specific user.
        Route::get('/edit/{user}', [UserController::class, 'edit']);

        // Additional routes for user management actions.
        Route::get('/roles', [UserManagementController::class, 'roles']);

        // Route to fetch permissions data for user management.
        Route::get('/permissions', [UserManagementController::class, 'permissions']);

        // Routes for handling user creation, updating, viewing, and deletion.
        Route::get('/{user}', [UserController::class, 'show']);
    });

    // Mutations: tighter limit - these create/change/delete accounts, roles, and permissions.
    Route::middleware('throttle:20,1')->group(function () {
        // Route to create a new role from the roles management page.
        Route::post('/roles', [UserManagementController::class, 'storeRole']);

        // Route to assign permissions to a specific role.
        Route::put('/roles/{role}/permissions', [UserManagementController::class, 'assignRolePermissions']);

        // Route to assign selected users to a specific role.
        Route::put('/roles/{role}/users', [UserManagementController::class, 'assignUsersToRole']);

        // Route to delete a role from the roles management page.
        Route::delete('/roles/{role}', [UserManagementController::class, 'destroyRole']);

        // Route to assign direct permissions to a specific user.
        Route::put('/{user}/permissions', [UserManagementController::class, 'assignPermissions']);

        // Route to handle the submission of the user creation form.
        Route::post('/', [UserController::class, 'store']);

        // Route to handle the submission of the user editing form for a specific user.
        Route::put('/{user}', [UserController::class, 'update']);

        // Route to handle the deletion of a specific user.
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });
});
