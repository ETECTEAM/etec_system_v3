<?php

namespace App\Modules\AbsenceBlock\Services;

use App\Models\ActivityLog;
use App\Models\User;

/**
 * Thin wrapper over the shared activity_logs trail for absence-block / rule
 * mutations. user_id is nullable so system-raised blocks (cron auto-record, a
 * QR submission) still get a row.
 */
class AbsenceBlockAudit
{
    /**
     * @param  array{rule_id?: int|null, block_id?: int|null, before?: array|null, after?: array|null}  $context
     */
    public function log(string $action, ?User $actor, array $context = []): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $actor?->id ?? auth()->id(),
            'action' => $action,
            'rule_id' => $context['rule_id'] ?? null,
            'block_id' => $context['block_id'] ?? null,
            'before' => $context['before'] ?? null,
            'after' => $context['after'] ?? null,
            'ip_address' => request()->ip(),
        ]);
    }
}
