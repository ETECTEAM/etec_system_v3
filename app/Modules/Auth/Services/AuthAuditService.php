<?php

namespace App\Modules\Auth\Services;

use App\Models\AuthAuditLog;
use App\Models\User;

/**
 * Records auth-related actions so registration, OTP, and approvals are traceable.
 */
class AuthAuditService
{
    public function log(?User $user, string $action, ?string $ipAddress = null, array $metadata = [], ?int $actorId = null): AuthAuditLog
    {
        return AuthAuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'ip_address' => $ipAddress,
            'metadata' => $metadata,
            'created_by' => $actorId,
        ]);
    }
}
