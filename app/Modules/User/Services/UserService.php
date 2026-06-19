<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\Data\StoreUserData;
use App\Modules\User\Data\UpdateUserData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Contains dashboard user-management business logic.
 */
class UserService
{
    /**
     * @return array<int, string>
     */
    public function assignableRolesFor(User $authUser): array
    {
        // Super admins can manage every built-in role.
        if ($authUser->hasRole('super_admin')) {
            return ['super_admin', 'admin', 'instructor', 'student'];
        }

        // Admins can only create/manage operational users.
        if ($authUser->hasRole('admin')) {
            return ['instructor', 'student'];
        }

        return [];
    }

    public function queryVisibleUsers(User $authUser): Builder
    {
        $query = User::query()->latest('id');

        // Visibility mirrors the same hierarchy as assignment permissions.
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

    public function paginateVisibleUsers(User $authUser, array $filters = [], int $perPage = 5): LengthAwarePaginator
    {
        $query = $this->queryVisibleUsers($authUser)->with('roles');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $role = trim((string) ($filters['role'] ?? ''));
        if ($role !== '') {
            $query->whereHas('roles', function (Builder $builder) use ($role): void {
                $builder->where('name', $role);
            });
        }

        return $query
            ->paginate($perPage)
            ->through(function (User $user): array {
                return $this->presentUser($user);
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

    public function presentUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->values(),
        ];
    }

    public function create(StoreUserData $data): User
    {
        $user = User::create($data->userAttributes());
        $user->syncRoles([$data->role]);

        return $user->fresh();
    }

    public function update(User $user, UpdateUserData $data): User
    {
        $user->update($data->userAttributes());
        $user->syncRoles([$data->role]);

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
