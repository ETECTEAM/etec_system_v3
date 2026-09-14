<?php

namespace App\Modules\AbsenceBlock\Policies;

use App\Models\User;

/**
 * Attendance-rule CRUD is open to admin + super_admin (super_admin also passes
 * via the Gate::before check in AppServiceProvider).
 */
class AttendanceRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function manage(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
