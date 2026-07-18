<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Modules\User\Services\UserService;

/**
 * Renders user role and permission management pages.
 */
class UserManagementController extends Controller
{
    // Show the list of roles. Only accessible to super admins.
    public function roles(Request $request): Response
    {
        // Only super admins and admins can access user management pages.
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        // Fetch roles with permission and user counts for the management matrix.
        $allPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return Inertia::render('backend/users/UserRoles', [
            'roles' => Role::query()
                ->with('permissions')
                ->withCount('users')
                ->orderBy('id')
                ->get()
                ->map(function (Role $role) use ($allPermissions): array {
                    // Super Admin implicitly has all permissions via Spatie gate,
                    // so we show the full list rather than an empty set.
                    $permissions = $role->name === 'super_admin'
                        ? $allPermissions
                        : $role->permissions->pluck('name')->values();

                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'users_count' => $role->users_count,
                        'permissions' => $permissions,
                    ];
                }),
            'permissions' => $allPermissions,
            'users' => User::query()
                ->with('roles')
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(function (User $user): array {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames()->values(),
                        'role_permissions_count' => $user->getPermissionsViaRoles()->count(),
                        'direct_permissions_count' => $user->getDirectPermissions()->count(),
                        'total_permissions_count' => $user->getAllPermissions()->count(),
                    ];
                }),
        ]);
    }

    // Assign permissions to a role so every user with that role inherits them.
    public function assignRolePermissions(Request $request, Role $role): RedirectResponse
    {
        // Only super admins and admins can change role permission defaults.
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', 'Role permissions updated successfully.');
    }

    // Create a new role from the role management page.
    public function storeRole(Request $request): RedirectResponse
    {
        // Only super admins and admins can create new roles.
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        // Normalise to snake_case BEFORE validation so the unique rule checks
        // the actual stored value. "Super Admin" → "super_admin" correctly
        // collides with the existing role rather than passing through.
        $request->merge([
            'name' => Str::snake(Str::lower(trim((string) $request->input('name', '')))),
        ]);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique check scoped to the sanctum guard (name + guard_name must be unique).
                Rule::unique('roles', 'name')->where('guard_name', 'sanctum'),
            ],
        ], [
            'name.required' => 'Role name is required.',
            'name.unique'   => 'A role with this name already exists.',
        ]);

        $role = Role::findOrCreate($request->input('name'), 'sanctum');

        return redirect('/dashboard/users/roles')->with('success', "Role '{$role->name}' created successfully.");
    }

    // Assign the selected users to a role from the role management page.
    public function assignUsersToRole(Request $request, Role $role): RedirectResponse
    {
        // Only super admins and admins can move users between roles.
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $validated = $request->validate([
            'users' => ['nullable', 'array'],
            'users.*' => ['required', 'integer', 'exists:users,id'],
        ]);

        User::query()
            ->whereIn('id', $validated['users'] ?? [])
            ->get()
            ->each(function (User $user) use ($role): void {
                $user->syncRoles([$role->name]);
            });

        // Role changes affect what the cached user table shows, so bust it.
        app(UserService::class)->invalidateUsersCache();

        return back()->with('success', 'Users assigned to role successfully.');
    }

    // Delete a role from the roles management page. Only super admins can do this.
    public function destroyRole(Request $request, Role $role): RedirectResponse
    {
        // Only super admins can delete roles.
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        // The super_admin role must always exist so admin access can never be locked out.
        if ($role->name === 'super_admin') {
            return back()->with('error', 'The Super Admin role cannot be deleted.');
        }

        // Refuse to delete a role that still has users assigned to it.
        if ($role->users()->exists()) {
            return back()->with('error', 'Reassign users before deleting this role.');
        }

        $role->delete();

        return redirect('/dashboard/users/roles')->with('success', "Role '{$role->name}' deleted successfully.");
    }

    // Show the list of permissions. Only accessible to super admins.
    public function permissions(Request $request): Response
    {
        // Only super admins and admins can access user management pages.
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        // Fetch users with role and direct permission data for assignment.
        return Inertia::render('backend/users/UserPermissions', [
            'users' => User::query()
                ->with(['roles', 'permissions'])
                ->latest('id')
                ->get(['id', 'name', 'email'])
                ->map(function (User $user): array {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->getRoleNames()->values(),
                        'direct_permissions' => $user->getDirectPermissions()->pluck('name')->values(),
                        'role_permissions' => $user->getPermissionsViaRoles()->pluck('name')->values(),
                        'all_permissions' => $user->getAllPermissions()->pluck('name')->values(),
                        'role_permissions_count' => $user->getPermissionsViaRoles()->count(),
                        'direct_permissions_count' => $user->getDirectPermissions()->count(),
                        'total_permissions_count' => $user->getAllPermissions()->count(),
                    ];
                }),
            'permissions' => Permission::query()
                ->where('guard_name', 'web')
                ->orderBy('name')
                ->pluck('name')
                ->values(),
        ]);
    }

    // Assign extra permissions directly to a user without changing the user's role.
    public function assignPermissions(Request $request, User $user): RedirectResponse
    {
        // Only super admins and admins can give user-specific permission overrides.
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ]);

        $user->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', 'User permissions updated successfully.');
    }
}
