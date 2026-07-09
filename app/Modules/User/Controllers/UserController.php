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

    /**
     * Show the users listing page (data itself is fetched separately via paginatedIndex).
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('backend/users/Index', [
            // Hides the "create user" action when the requester has no assignable roles left to grant.
            'canCreateUser' => $request->user() !== null && $this->userService->roleOptions($request->user())->isNotEmpty(),
        ]);
    }

    /**
     * Return a paginated, filtered JSON list of users for the index page's table/grid.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function paginatedIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        // Filters are all optional strings pulled straight from the query string;
        // paginateVisibleUsers() also scopes results to what $request->user() may see.
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

    /**
     * Show the "create user" form.
     *
     * @param  Request  $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('backend/users/Create', [
            // Only roles the current user is authorized to assign are offered.
            'roleOptions' => $this->userService->roleOptions($request->user()),
        ]);
    }

    /**
     * Show a single user's read-only profile page.
     *
     * @param  Request  $request
     * @param  User  $user  The user being viewed (route-model bound).
     * @return Response
     */
    public function show(Request $request, User $user): Response
    {
        $this->authorize('manage', $user);

        return Inertia::render('backend/users/Show', [
            // Eager-load role/student/instructor relations so presentUser() can build the full profile in one pass.
            'user' => $this->userService->presentUser($user->load(['roles', 'student', 'instructorData', 'creator'])),
        ]);
    }

    /**
     * Show the "edit user" form pre-filled with the target user's current data.
     *
     * @param  Request  $request
     * @param  User  $user  The user being edited (route-model bound).
     * @return Response
     */
    public function edit(Request $request, User $user): Response
    {
        $this->authorize('manage', $user);

        return Inertia::render('backend/users/Edit', [
            'user' => $this->userService->presentUser($user->load(['roles', 'student', 'instructorData', 'creator'])),
            'roleOptions' => $this->userService->roleOptions($request->user()),
        ]);
    }

    /**
     * Validate and persist a new user from the create form.
     *
     * @param  StoreUserRequest  $request  Validates input and exposes toData() as a DTO.
     * @return RedirectResponse
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $data = $request->toData();

        // Re-check role assignability server-side; the request's own rule only
        // limits the dropdown options, it doesn't stop a forged role value.
        $this->userService->ensureRoleIsAssignable($request->user(), $data->role);
        $this->userService->create($data, $request->user()?->id);

        return redirect('/dashboard/users')->with('success', 'User created successfully.');
    }

    /**
     * Validate and persist changes to an existing user from the edit form.
     *
     * @param  UpdateUserRequest  $request  Validates input and exposes toData() as a DTO.
     * @param  User  $user  The user being updated (route-model bound).
     * @return RedirectResponse
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('manage', $user);
        $data = $request->toData();

        // Re-check role assignability server-side; the request's own rule only
        // limits the dropdown options, it doesn't stop a forged role value.
        $this->userService->ensureRoleIsAssignable($request->user(), $data->role);
        $this->userService->update($user, $data);

        return redirect('/dashboard/users')->with('success', 'User updated successfully.');
    }

    /**
     * Delete a user account.
     *
     * @param  Request  $request
     * @param  User  $user  The user being deleted (route-model bound).
     * @return RedirectResponse
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manage', $user);
        $this->userService->delete($user);

        return redirect('/dashboard/users')->with('success', 'User deleted successfully.');
    }
}
