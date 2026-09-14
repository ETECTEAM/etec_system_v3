<?php

namespace App\Modules\AbsenceBlock\Policies;

use App\Models\User;

/**
 * Blacklist, approve/reject, settings and audit are open to admin + super_admin.
 * The ONE super_admin-exclusive capability is unlocking a hard lock (super_admin
 * also passes everything via the Gate::before check in AppServiceProvider).
 */
class StudentAttendanceBlockPolicy
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

    public function unlock(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function manageSettings(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function viewAudit(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
