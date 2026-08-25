<?php

namespace App\Modules\OfficialLeave\Services;

use App\Models\OfficialLeave;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Write-side use cases for official leaves: submission from a scanned QR, the
 * office's approve/reject decisions, revoke, and delete. Every mutation writes
 * an audit_logs row.
 */
class OfficialLeaveService
{
    public const MAX_LEAVE_DAYS = 'official-leave.max_leave_days';

    public function __construct(
        private readonly LeaveQrService $qrService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Creates a pending leave from a scanned QR token, burning the single-use
     * session in the same transaction. Throws ValidationException with a
     * student-facing message for every failure mode.
     */
    public function submitFromToken(string $plainToken, array $data): OfficialLeave
    {
        return DB::transaction(function () use ($plainToken, $data): OfficialLeave {
            $session = $this->qrService->lockForConsumption($plainToken);

            if (! $session) {
                throw ValidationException::withMessages([
                    'token' => 'This leave request link is invalid.',
                ]);
            }

            if ($session->isUsed()) {
                throw ValidationException::withMessages([
                    'token' => 'This leave request link was already used. Please ask the office for a new one.',
                ]);
            }

            if ($session->isExpired()) {
                throw ValidationException::withMessages([
                    'token' => 'This leave request link has expired. Please ask the office for a new one.',
                ]);
            }

            $startDate = Carbon::parse($data['start_date'])->startOfDay();
            $endDate = Carbon::parse($data['end_date'])->startOfDay();

            $conflict = $this->overlappingApprovedLeave($session->student_id, $startDate, $endDate);

            if ($conflict) {
                throw ValidationException::withMessages([
                    'start_date' => "You already have an approved leave from {$conflict->start_date->toDateString()} to {$conflict->end_date->toDateString()}.",
                ]);
            }

            $leave = OfficialLeave::query()->create([
                'student_id' => $session->student_id,
                'study_class_id' => null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => trim($data['reason']),
                'status' => OfficialLeave::STATUS_PENDING,
                'leave_request_session_id' => $session->id,
            ]);

            $session->update(['used_at' => now()]);

            return $leave;
        });
    }

    /**
     * Approves a pending leave. Re-checks overlap inside the same transaction so
     * two approvals racing each other can't both succeed.
     */
    public function approve(User $approver, OfficialLeave $leave, ?string $ip = null): OfficialLeave
    {
        return DB::transaction(function () use ($approver, $leave, $ip): OfficialLeave {
            $locked = OfficialLeave::query()->lockForUpdate()->find($leave->id);

            if (! $locked || $locked->status !== OfficialLeave::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending leaves can be approved.',
                ]);
            }

            $conflict = $this->overlappingApprovedLeave($locked->student_id, $locked->start_date, $locked->end_date, $locked->id);

            if ($conflict) {
                throw ValidationException::withMessages([
                    'overlap' => "Cannot approve: this student already has an approved leave from {$conflict->start_date->toDateString()} to {$conflict->end_date->toDateString()}.",
                ]);
            }

            $before = $this->snapshot($locked);

            $locked->update([
                'status' => OfficialLeave::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            $this->auditLogger->log($approver, AuditLogger::ACTION_LEAVE_APPROVED, $locked->id, $before, $this->snapshot($locked), $ip);

            return $locked;
        });
    }

    public function reject(User $rejecter, OfficialLeave $leave, string $note, ?string $ip = null): OfficialLeave
    {
        return DB::transaction(function () use ($rejecter, $leave, $note, $ip): OfficialLeave {
            $locked = OfficialLeave::query()->lockForUpdate()->find($leave->id);

            if (! $locked || $locked->status !== OfficialLeave::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending leaves can be rejected.',
                ]);
            }

            $before = $this->snapshot($locked);

            $locked->update([
                'status' => OfficialLeave::STATUS_REJECTED,
                'rejected_by' => $rejecter->id,
                'rejection_note' => trim($note),
            ]);

            $this->auditLogger->log($rejecter, AuditLogger::ACTION_LEAVE_REJECTED, $locked->id, $before, $this->snapshot($locked), $ip);

            return $locked;
        });
    }

    /**
     * Revocation is super_admin-anytime; admins may only revoke before the leave starts
     * (enforced by the policy, re-verified here since policies don't run inside the lock).
     */
    public function revoke(User $revoker, OfficialLeave $leave, ?string $note = null, ?string $ip = null): OfficialLeave
    {
        return DB::transaction(function () use ($revoker, $leave, $note, $ip): OfficialLeave {
            $locked = OfficialLeave::query()->lockForUpdate()->find($leave->id);

            if (! $locked || $locked->status !== OfficialLeave::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'status' => 'Only approved leaves can be revoked.',
                ]);
            }

            if (! $revoker->hasRole('super_admin') && $locked->start_date->startOfDay()->isPast()) {
                throw ValidationException::withMessages([
                    'status' => 'This leave has already started — only a super admin can revoke it.',
                ]);
            }

            $before = $this->snapshot($locked);

            $locked->update([
                'status' => OfficialLeave::STATUS_REVOKED,
                'revoked_by' => $revoker->id,
                'revoked_at' => now(),
                'revoked_note' => $note !== null ? trim($note) : null,
            ]);

            $this->auditLogger->log($revoker, AuditLogger::ACTION_LEAVE_REVOKED, $locked->id, $before, $this->snapshot($locked), $ip);

            return $locked;
        });
    }

    /**
     * Soft delete (super_admin only). The audit row survives with its
     * official_leave_id nulled by the FK's onDelete.
     */
    public function delete(User $deleter, OfficialLeave $leave, ?string $ip = null): void
    {
        $before = $this->snapshot($leave);

        $leave->delete();

        $this->auditLogger->log($deleter, AuditLogger::ACTION_LEAVE_DELETED, null, $before, null, $ip);
    }

    /**
     * True when the student has an approved leave covering $date (any class scope).
     */
    public function isOnApprovedLeave(int $studentId, Carbon|string|null $date = null): bool
    {
        $date = Carbon::parse($date ?? now())->toDateString();

        return OfficialLeave::query()
            ->where('student_id', $studentId)
            ->where('status', OfficialLeave::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    private function overlappingApprovedLeave(int $studentId, Carbon $start, Carbon $end, ?int $ignoreId = null): ?OfficialLeave
    {
        return OfficialLeave::query()
            ->where('student_id', $studentId)
            ->where('status', OfficialLeave::STATUS_APPROVED)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->first();
    }

    private function snapshot(OfficialLeave $leave): array
    {
        return [
            'status' => $leave->status,
            'start_date' => $leave->start_date?->toDateString(),
            'end_date' => $leave->end_date?->toDateString(),
            'reason' => $leave->reason,
            'rejection_note' => $leave->rejection_note,
            'approved_by' => $leave->approved_by,
            'rejected_by' => $leave->rejected_by,
            'revoked_by' => $leave->revoked_by,
        ];
    }
}
