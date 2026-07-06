<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\Instructor\Services\InstructorService;
use App\Modules\User\Data\StoreUserData;
use App\Modules\User\Data\UpdateUserData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Contains dashboard user-management business logic.
 */
class UserService
{
    public function __construct(
        private readonly InstructorService $instructorService,
    ) {}
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
        $query = User::query()->latest('id')->with(['student', 'instructorData']);

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
                    ->where('users.name', 'like', '%'.$search.'%')
                    ->orWhere('users.email', 'like', '%'.$search.'%')
                    ->orWhereHas('student', fn (Builder $query) => $query->where('full_name', 'like', '%'.$search.'%')->orWhere('student_code', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%'))
                    ->orWhereHas('instructorData', fn (Builder $query) => $query->where('full_name', 'like', '%'.$search.'%')->orWhere('instructor_code', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%'));
            });
        }

        $role = trim((string) ($filters['role'] ?? ''));
        if ($role !== '') {
            $query->role($role, 'web');
        }

        if (($filters['status'] ?? '') !== '') {
            $query->where('users.status', filter_var($filters['status'], FILTER_VALIDATE_BOOLEAN));
        }

        if (($filters['student_class'] ?? '') !== '') {
            $query->whereHas('student', fn (Builder $query) => $query->where('class_id', $filters['student_class']));
        }

        if (($filters['student_status'] ?? '') !== '') {
            $query->whereHas('student', fn (Builder $query) => $query->where('status', filter_var($filters['student_status'], FILTER_VALIDATE_BOOLEAN)));
        }

        foreach (['employment_type', 'shift_preference', 'available_for_class'] as $filter) {
            if (($filters[$filter] ?? '') !== '') {
                $value = $filter === 'available_for_class' ? filter_var($filters[$filter], FILTER_VALIDATE_BOOLEAN) : $filters[$filter];
                $query->whereHas('instructorData', fn (Builder $query) => $query->where($filter, $value));
            }
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
            ->where('guard_name', 'web')
            ->whereIn('name', $roles)
            ->orderBy('id')
            ->pluck('name')
            ->values();
    }

    public function presentUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->role === 'student' ? ($user->student?->full_name ?? $user->name) : ($user->role === 'instructor' ? ($user->instructorData?->full_name ?? $user->name) : $user->name),
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
            'status' => $user->status,
            'roles' => $user->getRoleNames()->values(),
            'student' => $user->student,
            'instructor_data' => $user->instructorData,
        ];
    }

    public function create(StoreUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data->name, 'email' => $data->email, 'password' => $data->password,
                'role' => $data->role, 'status' => $data->status,
            ]);
            $user->syncRoles([$data->role]);
            $this->syncPhoto($user, $data->avatar);
            $this->instructorService->syncProfile($user, $data->role, $data->student, $data->instructorData);
            return $user->fresh(['roles', 'student', 'instructorData', 'photo']);
        });
    }

    public function update(User $user, UpdateUserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $attributes = ['name' => $data->name, 'email' => $data->email, 'role' => $data->role, 'status' => $data->status];
            if ($data->password !== null && $data->password !== '') { $attributes['password'] = $data->password; }
            $user->update($attributes);
            $this->syncPhoto($user, $data->avatar);
            $user->syncRoles([$data->role]);
            $this->instructorService->syncProfile($user, $data->role, $data->student, $data->instructorData);
            return $user->fresh(['roles', 'student', 'instructorData', 'photo']);
        });
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

    private function syncPhoto(User $user, ?\Illuminate\Http\UploadedFile $avatar): void
    {
        if ($avatar === null) {
            return;
        }

        $previousPath = $user->photo?->file_path;

        $user->photo()->updateOrCreate(['user_id' => $user->id], [
            'file_path' => $avatar->store('avatars', 'public'),
            'file_name' => $avatar->getClientOriginalName(),
            'file_mime' => $avatar->getClientMimeType(),
            'file_size' => $avatar->getSize(),
        ]);

        if ($previousPath !== null && $previousPath !== '') {
            Storage::disk('public')->delete($previousPath);
        }
    }
}
