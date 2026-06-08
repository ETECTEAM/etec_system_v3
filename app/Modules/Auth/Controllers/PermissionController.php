<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\Permissions\AssignPermissionsToRoleRequest;
use App\Modules\Auth\Requests\Permissions\AssignPermissionsToUserRequest;
use App\Modules\Auth\Requests\Permissions\AssignRoleToUserRequest;
use App\Modules\Auth\Requests\Permissions\CreateFeaturePermissionsRequest;
use App\Modules\Auth\Requests\Permissions\CreatePermissionRequest;
use App\Modules\Auth\Requests\Permissions\CreateRoleRequest;
use App\Modules\Auth\Responses\Permissions\PermissionManagementResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function roles(): JsonResponse
    {
        return response()->json(
            Role::query()
                ->select(['id', 'name'])
                ->orderBy('id')
                ->get()
        );
    }

    public function createFeaturePermissions(CreateFeaturePermissionsRequest $request): JsonResponse
    {
        $data = $request->toData();

        $permissions = [];

        foreach ($data->actions as $action) {
            $permissionName = sprintf('%s.%s', $data->name, $action);
            $permission = Permission::findOrCreate($permissionName, $data->guardName);
            $permissions[] = $permission->name;
        }

        sort($permissions);

        return PermissionManagementResponse::featureCreated($data->name, $permissions);
    }

    public function createRole(CreateRoleRequest $request): JsonResponse
    {
        $data = $request->toData();

        $role = Role::findOrCreate($data->name, $data->guardName);

        return PermissionManagementResponse::roleCreated($role);
    }

    public function createPermission(CreatePermissionRequest $request): JsonResponse
    {
        $data = $request->toData();

        $permission = Permission::findOrCreate($data->name, $data->guardName);

        return PermissionManagementResponse::permissionCreated($permission);
    }

    public function assignPermissionsToRole(AssignPermissionsToRoleRequest $request): JsonResponse
    {
        $data = $request->toData();

        $role = Role::findByName($data->roleName, 'sanctum');
        $role->syncPermissions($data->permissions);

        return PermissionManagementResponse::rolePermissionsAssigned($role, $role->permissions()->pluck('name')->values());
    }

    public function assignRoleToUser(AssignRoleToUserRequest $request, User $user): JsonResponse
    {
        $data = $request->toData();

        $role = Role::findByName($data->roleName, 'sanctum');
        $user->syncRoles([$role]);

        return PermissionManagementResponse::roleAssignedToUser($user, $user->getRoleNames()->values());
    }

    public function assignPermissionsToUser(AssignPermissionsToUserRequest $request, User $user): JsonResponse
    {
        $data = $request->toData();

        $user->syncPermissions($data->permissions);

        return PermissionManagementResponse::permissionsAssignedToUser($user, $user->getAllPermissions()->pluck('name')->values());
    }
}
