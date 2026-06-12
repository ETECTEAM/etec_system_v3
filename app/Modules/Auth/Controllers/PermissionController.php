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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Use web guard for Laravel Inertia dashboard login.
     */
    private string $guardName = 'web';

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

        return PermissionManagementResponse::featureCreated(
            $data->name,
            $permissions
        );
    }

    /**
     * Create one role.
     * Example: admin, instructor, super_admin
     */
    public function createRole(CreateRoleRequest $request): JsonResponse
    {
        $data = $request->toData();

        $role = Role::findOrCreate(
            $data->name,
            $this->guardName
        );

        return PermissionManagementResponse::roleCreated($role);
    }

    /**
     * Create one permission.
     * Example: instructor.view
     */
    public function createPermission(CreatePermissionRequest $request): JsonResponse
    {
        $data = $request->toData();

        $permission = Permission::findOrCreate(
            $data->name,
            $this->guardName
        );

        return PermissionManagementResponse::permissionCreated($permission);
    }

    /**
     * Assign permissions to role.
     * Example: admin gets instructor.view, instructor.create, instructor.update, instructor.delete
     */
    public function assignPermissionsToRole(AssignPermissionsToRoleRequest $request): JsonResponse
    {
        $data = $request->toData();

        $role = Role::findByName(
            $data->roleName,
            $this->guardName
        );

        $role->syncPermissions($data->permissions);

        return PermissionManagementResponse::rolePermissionsAssigned(
            $role,
            $role->permissions()->pluck('name')->values()
        );
    }

    /**
     * Assign role to user.
     * Example: user gets admin role.
     */
    public function assignRoleToUser(AssignRoleToUserRequest $request, User $user): JsonResponse
    {
        $data = $request->toData();

        $role = Role::findByName(
            $data->roleName,
            $this->guardName
        );

        $user->syncRoles([$role]);

        return PermissionManagementResponse::roleAssignedToUser(
            $user,
            $user->getRoleNames()->values()
        );
    }

    /**
     * Assign permissions directly to user.
     */
    public function assignPermissionsToUser(AssignPermissionsToUserRequest $request, User $user): JsonResponse
    {
        $data = $request->toData();

        $user->syncPermissions($data->permissions);

        return PermissionManagementResponse::permissionsAssignedToUser(
            $user,
            $user->getAllPermissions()->pluck('name')->values()
        );
    }
}