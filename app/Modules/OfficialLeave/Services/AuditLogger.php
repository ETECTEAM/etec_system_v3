<?php

namespace App\Modules\OfficialLeave\Services;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Writes official-leave feature rows to the append-only audit_logs table.
 */
class AuditLogger
{
    public const ACTION_QR_GENERATED = 'qr.generated';

    public const ACTION_LEAVE_SUBMITTED = 'leave.submitted';

    public const ACTION_LEAVE_APPROVED = 'leave.approved';

    public const ACTION_LEAVE_REJECTED = 'leave.rejected';

    public const ACTION_LEAVE_REVOKED = 'leave.revoked';

    public const ACTION_LEAVE_DELETED = 'leave.deleted';

    public const ACTION_SETTINGS_UPDATED = 'settings.updated';

    public function log(
        ?User $user,
        string $action,
        ?int $officialLeaveId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $ip = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'official_leave_id' => $officialLeaveId,
            'before' => $before,
            'after' => $after,
            'ip' => $ip,
        ]);
    }
}
