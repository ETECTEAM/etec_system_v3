<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use App\Modules\User\Requests\StoreUserRequest;
use App\Modules\User\Services\UserService;

/**
 * Renders user role and permission management pages.
 */
class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        // Only super admins can access user management pages.
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        // Fetch users with their roles and pass to the Inertia view.
        return Inertia::render('backend/users/Index', [

            // Eager load roles to avoid N+1 query problem.
            'users' => User::query()
                ->latest('id')
                ->with('roles')
                ->get(['id', 'name', 'email'])
                ->map(function (User $user): array {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames()->values(),
                    ];
                }),
        ]);
    }

    // Show the form to create a new user. 
    public function create(Request $request): Response
    {
        // Only super admins can access user management pages.
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        // Fetch all available roles to populate the role selection in the form.
        return Inertia::render('backend/users/Create', [
            'roleOptions' => Role::query()->pluck('name')->values(),
        ]);
    }

    // Show the list of roles. Only accessible to super admins.
    public function roles(Request $request): Response
    {
        // Only super admins can access user management pages.
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        // Fetch all available roles to display in the view.
        return Inertia::render('backend/users/Roles', [
            'roles' => Role::query()->pluck('name')->values(),
        ]);
    }

    // Show the list of permissions. Only accessible to super admins.
    public function permissions(Request $request): Response
    {
        // Only super admins can access user management pages.
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        // Fetch all available permissions to display in the view.
        return Inertia::render('backend/users/Permissions', [
            'permissions' => Permission::query()->pluck('name')->values(),
        ]);
    }

    // Handle the form submission to create a new user. Only accessible to super admins.
    public function store(StoreUserRequest $request): RedirectResponse
    {
        // Only super admins can access user management pages.
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        // Convert validated input into a DTO and delegate creation to the service.
        $data = $request->toData();

        /** @var UserService $service */
        $service = app(UserService::class);

        $service->create($data);

        return redirect('/dashboard/users/create')->with('success', 'User created successfully.');
    }
}
