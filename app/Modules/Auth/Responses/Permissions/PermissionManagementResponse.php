<?php

namespace App\Modules\Auth\Responses\Permissions;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionManagementResponse
{
    /**
     * @param  array<int, string>  $permissions
     */
    public static function featureCreated(string $feature, array $permissions)
    {
        return response()->json([
            'message' => 'Feature permissions created successfully.',
            'feature' => $feature,
            'permissions' => $permissions,
        ], 201);
    }

    public static function roleCreated(Role $role)
    {
        return response()->json([
            'message' => 'Role created successfully.',
            'role' => self::rolePayload($role),
        ], 201);
    }

    public static function permissionCreated(Permission $permission)
    {
        return response()->json([
            'message' => 'Permission created successfully.',
            'permission' => self::permissionPayload($permission),
        ], 201);
    }

    /**
     * @param  Collection<int, string>  $permissions
     */
    public static function rolePermissionsAssigned(Role $role, Collection $permissions)
    {
        return response()->json([
            'message' => 'Permissions assigned to role successfully.',
            'role' => $role->name,
            'permissions' => $permissions,
        ]);
    }

    /**
     * @param  Collection<int, string>  $roles
     */
    public static function roleAssignedToUser(User $user, Collection $roles)
    {
        return response()->json([
            'message' => 'Role assigned to user successfully.',
            'user' => self::userPayload($user),
            'roles' => $roles,
        ]);
    }

    /**
     * @param  Collection<int, string>  $permissions
     */
    public static function permissionsAssignedToUser(User $user, Collection $permissions)
    {
        return response()->json([
            'message' => 'Permissions assigned to user successfully.',
            'user' => self::userPayload($user),
            'permissions' => $permissions,
        ]);
    }

    /**
     * @return array{id: int, name: string, guard_name: string}
     */
    private static function rolePayload(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
        ];
    }

    /**
     * @return array{id: int, name: string, guard_name: string}
     */
    private static function permissionPayload(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
        ];
    }

    /**
     * @return array{id: int, name: string, email: string}
     */
    private static function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
