<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Holds reusable authentication helpers for roles, login lookup, and API payloads.
 */
class AuthService
{
    public function ensureDefaultRole(string $roleName = 'instructor', string $guard = 'sanctum'): Role
    {
        return Role::findOrCreate($roleName, $guard);
    }

    public function findUserForLogin(string $login): ?User
    {
        if ($login === '') {
            return null;
        }

        // Let users log in with either email or username from the same field.
        $column = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        return User::query()
            ->where($column, $login)
            ->first();
    }

    public function buildAuthPayload(User $user, ?string $token = null, ?string $message = null): array
    {
        $payload = [
            'user' => $this->sanitizeUser($user),
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $this->groupPermissionsByResource(
                $user->getAllPermissions()->pluck('name')->all()
            ),
        ];

        if ($token !== null) {
            $payload['token'] = $token;
        }

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return $payload;
    }

    private function groupPermissionsByResource(array $permissions): array
    {
        $grouped = [];

        // Convert permission names like course.view into course => [course.view].
        foreach ($permissions as $permission) {
            $resource = explode('.', $permission, 2)[0];
            $grouped[$resource] ??= [];
            $grouped[$resource][] = $permission;
        }

        ksort($grouped);

        foreach ($grouped as &$resourcePermissions) {
            sort($resourcePermissions);
        }

        return $grouped;
    }

    private function sanitizeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
