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

   public function index(Request $request): Response
{
    if (! $this->canViewInstructors($request->user())) {
        abort(403);
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
        $this->authorize('create', User::class);

        return Inertia::render('backend/users/Create', [
            'roleOptions' => $this->userService->roleOptions($request->user()),
        ]);
    }

  public function show(Request $request, User $user): Response
{
    if (! $this->canViewInstructors($request->user())) {
        abort(403);
    }

    if (! $user->hasRole('instructor')) {
        abort(404);
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
        $this->authorize('create', User::class);
        $data = $request->toData();

        // The request validates role format; the service enforces role authority.
        $this->userService->ensureRoleIsAssignable($request->user(), $data->role);
        $this->userService->create($data);

        return redirect('/dashboard/users')->with('success', 'User created successfully.');
    }

  public function update(UpdateUserRequest $request, User $user): RedirectResponse
{
    if (! $this->canManageInstructors($request->user())) {
        abort(403);
    }

    $data = $request->toData();

    $this->userService->ensureRoleIsAssignable($request->user(), $data->role);
    $this->userService->update($user, $data);

    return redirect('/dashboard/users')->with('success', 'Instructor updated successfully.');
}

    public function destroy(Request $request, User $user): RedirectResponse
{
    if (! $this->canManageInstructors($request->user())) {
        abort(403);
    }

    $this->userService->delete($user);

    return redirect('/dashboard/users')->with('success', 'Instructor deleted successfully.');
}
}
