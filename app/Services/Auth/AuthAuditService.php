<?php

namespace App\Services\Auth;

use App\Models\AuthAuditLog;
use App\Models\User;

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
