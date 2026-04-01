<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionManagementController extends Controller
{
    public function createFeaturePermissions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'actions' => ['nullable', 'array', 'min:1'],
            'actions.*' => ['required', 'string', 'max:255'],
            'guard_name' => ['nullable', 'string', 'max:255'],
        ]);

        $guardName = $validated['guard_name'] ?? 'sanctum';
        $actions = $validated['actions'] ?? ['view', 'create', 'update', 'delete'];
        $permissions = [];

        foreach ($actions as $action) {
            $permissionName = sprintf('%s.%s', $validated['name'], $action);
            $permission = Permission::findOrCreate($permissionName, $guardName);
            $permissions[] = $permission->name;
        }

        sort($permissions);

        return response()->json([
            'message' => 'Feature permissions created successfully.',
            'feature' => $validated['name'],
            'permissions' => $permissions,
        ], 201);
    }

    public function createRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'guard_name' => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::findOrCreate(
            $validated['name'],
            $validated['guard_name'] ?? 'sanctum'
        );

        return response()->json([
            'message' => 'Role created successfully.',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
            ],
        ], 201);
    }

    public function createPermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'guard_name' => ['nullable', 'string', 'max:255'],
        ]);

        $permission = Permission::findOrCreate(
            $validated['name'],
            $validated['guard_name'] ?? 'sanctum'
        );

        return response()->json([
            'message' => 'Permission created successfully.',
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
            ],
        ], 201);
    }

    public function assignPermissionsToRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_name' => ['required', 'string', 'exists:roles,name'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ]);

        $role = Role::findByName($validated['role_name'], 'sanctum');
        $role->syncPermissions($validated['permissions']);

        return response()->json([
            'message' => 'Permissions assigned to role successfully.',
            'role' => $role->name,
            'permissions' => $role->permissions()->pluck('name')->values(),
        ]);
    }

    public function assignRoleToUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role_name' => ['required', 'string', 'exists:roles,name'],
        ]);

        $role = Role::findByName($validated['role_name'], 'sanctum');
        $user->syncRoles([$role]);

        return response()->json([
            'message' => 'Role assigned to user successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => $user->getRoleNames()->values(),
        ]);
    }

    public function assignPermissionsToUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ]);

        $user->syncPermissions($validated['permissions']);

        return response()->json([
            'message' => 'Permissions assigned to user successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ]);
    }
}
