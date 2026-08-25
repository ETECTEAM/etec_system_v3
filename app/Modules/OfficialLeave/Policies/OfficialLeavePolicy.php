<?php

namespace App\Modules\OfficialLeave\Policies;

use App\Models\OfficialLeave;
use App\Models\User;

/**
 * Role gates for the official-leave feature. Admin (school office) reviews and
 * decides pending requests; super_admin additionally revokes anytime, deletes,
 * and owns reports/settings/audit-log.
 */
class OfficialLeavePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function approve(User $user, OfficialLeave $leave): bool
    {
        return $this->decide($user, $leave);
    }

    public function reject(User $user, OfficialLeave $leave): bool
    {
        return $this->decide($user, $leave);
    }

    private function decide(User $user, OfficialLeave $leave): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        // Decisions only apply to live pending rows — a deleted leave can't be revived.
        if ($leave->trashed() || $leave->status !== OfficialLeave::STATUS_PENDING) {
            return false;
        }

        return true;
    }

    /**
     * Super_admin may revoke an approved leave at any time; admin only while it
     * hasn't started yet.
     */
    public function revoke(User $user, OfficialLeave $leave): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($leave->trashed() || $leave->status !== OfficialLeave::STATUS_APPROVED) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return ! $leave->start_date->startOfDay()->isPast();
    }

    public function delete(User $user, OfficialLeave $leave): bool
    {
        return $user->hasRole('super_admin')
            && ! $leave->trashed()
            && in_array($leave->status, [OfficialLeave::STATUS_REJECTED, OfficialLeave::STATUS_PENDING], true);
    }

    public function viewReports(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function manageSettings(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function viewActivityLog(User $user): bool
    {
        return $user->hasRole('super_admin');
    }
}
