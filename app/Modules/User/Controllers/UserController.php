<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\User\Requests\StoreUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
            'users' => $this->userService->listVisibleUsers($request->user()),
            'canCreateUser' => $request->user() !== null && $this->userService->roleOptions($request->user())->isNotEmpty(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('backend/users/Create', [
            'roleOptions' => $this->userService->roleOptions($request->user()),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $this->userService->ensureRoleIsAssignable($request->user(), $request->validated('role'));

        $this->userService->create($request->validated());

        return redirect('/dashboard/users')->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('manage', $user);
        $validated = $request->validated();
        $this->userService->ensureRoleIsAssignable($request->user(), $validated['role']);

        if (($validated['password'] ?? null) === null || $validated['password'] === '') {
            unset($validated['password']);
        }

        unset($validated['password_confirmation']);

        $this->userService->update($user, $validated);

        return redirect('/dashboard/users')->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage', $user);

        $this->userService->delete($user);

        return redirect('/dashboard/users')->with('success', 'User deleted successfully.');
    }
}
