<?php

namespace App\Modules\OfficialLeave\Services;

use App\Models\LeaveRequestSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * Owns the leave-request QR lifecycle: create a hashed single-use token, build its
 * signed URL, and resolve a scanned token. Marking a session used happens inside
 * OfficialLeaveService's submission transaction so the token burns exactly when
 * (and only if) the leave is actually created.
 */
class LeaveQrService
{
    public function createSession(Student $student, User $creator): array
    {
        $token = bin2hex(random_bytes(32));
        $ttlMinutes = max(1, (int) official_leave_setting('qr_token_ttl_minutes'));

        $session = LeaveRequestSession::query()->create([
            'student_id' => $student->id,
            'created_by' => $creator->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        return [
            'session_id' => $session->id,
            // Signature expiry and row expiry share one clock, so both agree on
            // the exact moment the QR dies.
            'url' => URL::temporarySignedRoute('leave.form', $session->expires_at, ['token' => $token]),
            'expires_at' => $session->expires_at->toIso8601String(),
            'ttl_seconds' => $ttlMinutes * 60,
        ];
    }

    /**
     * Read-only check for rendering the form: valid | not_found | already_used | expired.
     * Nothing is marked here — a student who opens but doesn't submit keeps the QR usable.
     *
     * @return array{session: LeaveRequestSession|null, state: string}
     */
    public function resolve(string $plainToken): array
    {
        $session = LeaveRequestSession::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (! $session) {
            return ['session' => null, 'state' => 'not_found'];
        }

        if ($session->isUsed()) {
            return ['session' => $session, 'state' => 'already_used'];
        }

        if ($session->isExpired()) {
            return ['session' => $session, 'state' => 'expired'];
        }

        return ['session' => $session, 'state' => 'valid'];
    }

    /**
     * Locked lookup for submission — the caller runs inside its own transaction and
     * must verify state again before creating the leave.
     */
    public function lockForConsumption(string $plainToken): ?LeaveRequestSession
    {
        return LeaveRequestSession::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->lockForUpdate()
            ->first();
    }

    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
