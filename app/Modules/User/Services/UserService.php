<?php

namespace App\Modules\User\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserService
{
    /**
     * @return array<int, string>
     */
    public function assignableRolesFor(User $authUser): array
    {
        if ($authUser->hasRole('super_admin')) {
            return ['super_admin', 'admin', 'instructor', 'student'];
        }

        if ($authUser->hasRole('admin')) {
            return ['instructor', 'student'];
        }

        return [];
    }

    public function queryVisibleUsers(User $authUser): Builder
    {
        $query = User::query()->latest('id');

        if ($authUser->hasRole('super_admin')) {
            return $query;
        }

        if ($authUser->hasRole('admin')) {
            return $query->whereHas('roles', function (Builder $builder): void {
                $builder->whereIn('name', ['instructor', 'student']);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public function listVisibleUsers(User $authUser): Collection
    {
        return $this->queryVisibleUsers($authUser)
            ->get(['id', 'name', 'email'])
            ->map(function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values(),
                ];
            });
    }

    public function roleOptions(User $authUser): Collection
    {
        $roles = $this->assignableRolesFor($authUser);

        return Role::query()
            ->whereIn('name', $roles)
            ->orderBy('id')
            ->pluck('name')
            ->values();
    }

    public function create(array $data): User
    {
        $role = $data['role'];
        unset($data['role']);

        $user = User::create($data);
        $user->syncRoles([$role]);

        return $user->fresh();
    }

    public function update(User $user, array $data): User
    {
        $role = $data['role'] ?? null;
        unset($data['role']);

        $user->update($data);

        if ($role !== null) {
            $user->syncRoles([$role]);
        }

        return $user->fresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function ensureRoleIsAssignable(User $authUser, string $role): void
    {
        if (! in_array($role, $this->assignableRolesFor($authUser), true)) {
            throw ValidationException::withMessages([
                'role' => 'You are not allowed to assign this role.',
            ]);
        }
    }
}
