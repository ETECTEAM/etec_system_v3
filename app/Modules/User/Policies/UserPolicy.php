<?php

namespace App\Modules\User\Policies;

use App\Models\User;

/**
 * Defines who can view and manage dashboard users.
 */
class UserPolicy
{
    private function isAdminType(User $authUser): bool
    {
        return $authUser->hasRole('super_admin') || $authUser->hasRole('admin');
    }

    public function viewAny(User $authUser): bool
    {
        return $this->isAdminType($authUser) && $authUser->can('view-users');
    }

    public function create(User $authUser): bool
    {
        return $this->isAdminType($authUser) && $authUser->can('create-users');
    }

    // Base role/target eligibility check - who an admin-type user is allowed
    // to touch at all, independent of which specific action they're taking.
    public function manage(User $authUser, User $targetUser): bool
    {
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        if ($authUser->hasRole('admin')) {
            return $targetUser->hasAnyRole(['instructor', 'student']);
        }

        return false;
    }

    public function view(User $authUser, User $targetUser): bool
    {
        return $this->manage($authUser, $targetUser) && $authUser->can('view-users');
    }

    public function update(User $authUser, User $targetUser): bool
    {
        return $this->manage($authUser, $targetUser) && $authUser->can('edit-users');
    }

    public function delete(User $authUser, User $targetUser): bool
    {
        return $this->manage($authUser, $targetUser) && $authUser->can('delete-users');
    }
}
