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

    // Route to handle the submission of the user creation form.
    Route::post('/', [UserController::class, 'store']);
    
    // Route to handle the submission of the user editing form for a specific user.
    Route::put('/{user}', [UserController::class, 'update']);

    // Route to handle the deletion of a specific user.
    Route::delete('/{user}', [UserController::class, 'destroy']);
});
