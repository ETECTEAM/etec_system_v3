<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Requests\Permissions\AssignPermissionsToRoleRequest;
use App\Modules\Auth\Requests\Permissions\AssignPermissionsToUserRequest;
use App\Modules\Auth\Requests\Permissions\AssignRoleToUserRequest;
use App\Modules\Auth\Requests\Permissions\CreateFeaturePermissionsRequest;
use App\Modules\Auth\Requests\Permissions\CreatePermissionRequest;
use App\Modules\Auth\Requests\Permissions\CreateRoleRequest;
use App\Modules\Auth\Responses\Permissions\PermissionManagementResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    /**
     * Use web guard for Laravel Inertia dashboard login.
     */
    private string $guardName = 'web';

    /**
     * Show Inertia page: User & Permission.
     *
     * This method is required because your Vue page expects:
     * - users
     * - permissions
     */
    public function userPermissionsPage(): Response
    {
        $users = User::query()
            ->with(['roles', 'permissions'])
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,

                    /**
                     * User roles.
                     * Example: ['admin', 'instructor']
                     */
                    'roles' => $user->getRoleNames()->values(),

                    /**
                     * Permissions from role only.
                     * These show as pale checks in your Vue matrix.
                     */
                    'role_permissions' => $user->getPermissionsViaRoles()
                        ->pluck('name')
                        ->values(),

                    /**
                     * Direct user permissions only.
                     * These show as blue checks in your Vue matrix.
                     */
                    'direct_permissions' => $user->getDirectPermissions()
                        ->pluck('name')
                        ->values(),
                ];
            })
            ->values();

        /**
         * All permissions for the matrix.
         * Example:
         * user.view
         * user.create
         * room.view
         * room.create
         */
        $permissions = Permission::query()
            ->where('guard_name', $this->guardName)
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return Inertia::render('dashboard/users/Permission', [
            'users' => $users,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Save direct permissions for selected user from Inertia page.
     *
     * Your Vue calls:
     * PUT /dashboard/users/{id}/permissions
     */
    public function updateUserPermissions(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')
                    ->where('guard_name', $this->guardName),
            ],
        ]);

        /**
         * This only updates direct permissions.
         * It does not change the user's role.
         */
        $user->syncPermissions($data['permissions'] ?? []);

        /**
         * Clear Spatie cache after permission update.
         */
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', 'User permissions updated successfully.');
    }

    /**
     * Get all roles.
     */
    public function roles(): JsonResponse
    {
        return response()->json(
            Role::query()
                ->select(['id', 'name', 'guard_name'])
                ->where('guard_name', $this->guardName)
                ->orderBy('id')
                ->get()
        );
    }

    /**
     * Create many permissions for one feature.
     *
     * Example:
     * feature = instructor
     * actions = view, create, update, delete
     *
     * Result:
     * instructor.view
     * instructor.create
     * instructor.update
     * instructor.delete
     */
    public function createFeaturePermissions(CreateFeaturePermissionsRequest $request): JsonResponse
    {
        $data = $request->toData();

        $permissions = [];

        foreach ($data->actions as $action) {
            $permissionName = sprintf('%s.%s', $data->name, $action);

            $permission = Permission::findOrCreate(
                $permissionName,
                $this->guardName
            );

            $permissions[] = $permission->name;
        }

        sort($permissions);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return PermissionManagementResponse::featureCreated(
            $data->name,
            $permissions
        );
    }

    /**
     * Create one role.
     *
     * Example:
     * admin
     * instructor
     * super_admin
     */
    public function createRole(CreateRoleRequest $request): JsonResponse
    {
        $data = $request->toData();

        $role = Role::findOrCreate(
            $data->name,
            $this->guardName
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return PermissionManagementResponse::roleCreated($role);
    }

    /**
     * Create one permission.
     *
     * Example:
     * instructor.view
     */
    public function createPermission(CreatePermissionRequest $request): JsonResponse
    {
        $data = $request->toData();

        $permission = Permission::findOrCreate(
            $data->name,
            $this->guardName
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return PermissionManagementResponse::permissionCreated($permission);
    }

    /**
     * Assign permissions to role.
     *
     * Example:
     * admin gets:
     * instructor.view
     * instructor.create
     */
    public function assignPermissionsToRole(AssignPermissionsToRoleRequest $request): JsonResponse
    {
        $data = $request->toData();

        $role = Role::findByName(
            $data->roleName,
            $this->guardName
        );

        $role->syncPermissions($data->permissions);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return PermissionManagementResponse::rolePermissionsAssigned(
            $role,
            $role->permissions()->pluck('name')->values()
        );
    }

    /**
     * Assign role to user.
     *
     * Example:
     * user gets admin role.
     */
    public function assignRoleToUser(AssignRoleToUserRequest $request, User $user): JsonResponse
    {
        $data = $request->toData();

        $role = Role::findByName(
            $data->roleName,
            $this->guardName
        );

        $user->syncRoles([$role]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return PermissionManagementResponse::roleAssignedToUser(
            $user,
            $user->getRoleNames()->values()
        );
    }

    /**
     * Assign permissions directly to user by API.
     *
     * This is JSON API version.
     * Your Inertia page uses updateUserPermissions() above.
     */
    public function assignPermissionsToUser(AssignPermissionsToUserRequest $request, User $user): JsonResponse
    {
        $data = $request->toData();

        $user->syncPermissions($data->permissions);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return PermissionManagementResponse::permissionsAssignedToUser(
            $user,
            $user->getAllPermissions()->pluck('name')->values()
        );
    }
}