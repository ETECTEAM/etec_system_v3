<?php

namespace App\Modules\OfficialLeave\Policies;

use App\Models\OfficialLeave;
use App\Models\User;

class OfficialLeavePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function approve(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function reject(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function revoke(User $user, OfficialLeave $leave): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin can only revoke if leave hasn't started yet
        if ($user->hasRole('admin')) {
            return $leave->start_date->isFuture();
        }

        return false;
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function manageSettings(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function viewReports(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function viewActivityLog(User $user): bool
    {
        return $user->hasRole('super_admin');
    }
}
