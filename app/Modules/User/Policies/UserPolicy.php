<?php

namespace App\Modules\User\Policies;

use App\Models\User;

/**
 * Defines who can view and manage dashboard users.
 */
class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasRole('super_admin') || $authUser->hasRole('admin');
    }

    public function create(User $authUser): bool
    {
        return $this->viewAny($authUser);
    }

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

    public function update(User $authUser, User $targetUser): bool
    {
        return $this->manage($authUser, $targetUser);
    }

    public function delete(User $authUser, User $targetUser): bool
    {
        return $this->manage($authUser, $targetUser);
    }
}
