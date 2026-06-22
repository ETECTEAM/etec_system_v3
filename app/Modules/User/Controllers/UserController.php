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
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('backend/users/Index', [
            'canCreateUser' => $request->user() !== null && $this->userService->roleOptions($request->user())->isNotEmpty(),
        ]);
    }

    public function paginatedIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->paginateVisibleUsers($request->user(), [
            'search' => $request->string('search')->toString(),
            'role' => $request->string('role')->toString(),
            'status' => $request->string('status')->toString(),
            'student_class' => $request->string('student_class')->toString(),
            'student_status' => $request->string('student_status')->toString(),
            'employment_type' => $request->string('employment_type')->toString(),
            'shift_preference' => $request->string('shift_preference')->toString(),
            'available_for_class' => $request->string('available_for_class')->toString(),
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
        $this->authorize('manage', $user);

        return Inertia::render('backend/users/Show', [
            'user' => $this->userService->presentUser($user->load(['roles', 'studentData', 'instructorData'])),
        ]);
    }

    public function edit(Request $request, User $user): Response
    {
        $this->authorize('manage', $user);

        return Inertia::render('backend/users/Edit', [
            'user' => $this->userService->presentUser($user->load(['roles', 'studentData', 'instructorData'])),
            'roleOptions' => $this->userService->roleOptions($request->user()),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $data = $request->toData();

        $this->userService->ensureRoleIsAssignable($request->user(), $data->role);
        $this->userService->create($data);

        return redirect('/dashboard/users')->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('manage', $user);
        $data = $request->toData();

        $this->userService->ensureRoleIsAssignable($request->user(), $data->role);
        $this->userService->update($user, $data);

        return redirect('/dashboard/users')->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage', $user);
        $this->userService->delete($user);

        return redirect('/dashboard/users')->with('success', 'User deleted successfully.');
    }
}
