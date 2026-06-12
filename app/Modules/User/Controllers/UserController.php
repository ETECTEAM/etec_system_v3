<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\User\Requests\StoreUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Coordinates dashboard user-management pages and actions.
 */
class UserController extends Controller
{
    private function canViewInstructors(User $user): bool
{
    return $user->hasRole('admin') || $user->hasRole('instructor');
}

private function canManageInstructors(User $user): bool
{
    return $user->hasRole('admin');
}
    public function __construct(
        private readonly UserService $userService
    ) {
    }

<<<<<<< HEAD
   public function index(Request $request): Response
{
    if (! $this->canViewInstructors($request->user())) {
        abort(403);
=======
    public function index(Request $request): Response
    {
        // Allow only users with permission to view the user list.
        $this->authorize('viewAny', User::class);

        // Show the create button only when the user can assign at least one role.
        return Inertia::render('backend/users/Index', [
            'canCreateUser' => $request->user() !== null && $this->userService->roleOptions($request->user())->isNotEmpty(),
        ]);
>>>>>>> origin/dev
    }

    return Inertia::render('backend/users/Index', [
        'canCreateUser' => false,
    ]);
}
    public function paginatedIndex(Request $request): JsonResponse
    {
        if (! $this->canViewInstructors($request->user())) {
            abort(403);
        }

        $users = $this->userService->paginateVisibleUsers($request->user(), [
            'search' => $request->string('search')->toString(),
            'role' => 'instructor',
        ], 5);

        return response()->json($users);
    }

    public function create(Request $request): Response
    {
        // The policy decides whether this user can create another user.
        $this->authorize('create', User::class);

        // Send only roles that the current user is allowed to assign.
        return Inertia::render('backend/users/Create', [
            'roleOptions' => $this->userService->roleOptions($request->user()),
        ]);
    }

<<<<<<< HEAD
  public function show(Request $request, User $user): Response
{
    if (! $this->canViewInstructors($request->user())) {
        abort(403);
    }

    if (! $user->hasRole('instructor')) {
        abort(404);
=======
    public function show(Request $request, User $user): Response
    {
        // A user must be manageable by the current user before details are shown.
        $this->authorize('manage', $user);

        // Present the user through the service so the frontend receives a stable shape.
        return Inertia::render('backend/users/Show', [
            'user' => $this->userService->presentUser($user),
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        // Reuse the manage policy for edit access.
        $this->authorize('manage', $user);

        // Load the editable user plus the roles available to the current admin.
        return Inertia::render('backend/users/Edit', [
            'user' => $this->userService->presentUser($user),
            'roleOptions' => $this->userService->roleOptions($request->user()),
        ]);
>>>>>>> origin/dev
    }

    return Inertia::render('backend/users/Show', [
        'user' => $this->userService->presentUser($user),
    ]);
}

   public function edit(Request $request, User $user): Response
{
    if (! $this->canManageInstructors($request->user())) {
        abort(403);
    }

    return Inertia::render('backend/users/Edit', [
        'user' => $this->userService->presentUser($user),
        'roleOptions' => $this->userService->roleOptions($request->user()),
    ]);
}

    public function store(StoreUserRequest $request): RedirectResponse
    {
        // The form request validates input; the policy validates creation permission.
        $this->authorize('create', User::class);
        $data = $request->toData();

        // The request validates role format; the service enforces role authority.
        $this->userService->ensureRoleIsAssignable($request->user(), $data->role);
        $this->userService->create($data);

        return redirect('/dashboard/users')->with('success', 'User created successfully.');
    }

<<<<<<< HEAD
  public function update(UpdateUserRequest $request, User $user): RedirectResponse
{
    if (! $this->canManageInstructors($request->user())) {
        abort(403);
=======
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // Only managers of this user can update their account details.
        $this->authorize('manage', $user);
        $data = $request->toData();

        // Re-check assignability before changing a user's role.
        $this->userService->ensureRoleIsAssignable($request->user(), $data->role);
        $this->userService->update($user, $data);

        return redirect('/dashboard/users')->with('success', 'User updated successfully.');
>>>>>>> origin/dev
    }

    $data = $request->toData();

    $this->userService->ensureRoleIsAssignable($request->user(), $data->role);
    $this->userService->update($user, $data);

    return redirect('/dashboard/users')->with('success', 'Instructor updated successfully.');
}

    public function destroy(Request $request, User $user): RedirectResponse
<<<<<<< HEAD
{
    if (! $this->canManageInstructors($request->user())) {
        abort(403);
=======
    {
        // Deletion uses the same management rule as viewing and editing.
        $this->authorize('manage', $user);

        // Keep delete behavior inside the service in case cleanup rules change later.
        $this->userService->delete($user);

        return redirect('/dashboard/users')->with('success', 'User deleted successfully.');
>>>>>>> origin/dev
    }

    $this->userService->delete($user);

    return redirect('/dashboard/users')->with('success', 'Instructor deleted successfully.');
}
}
