<?php

namespace App\Modules\OfficialLeave\Services;

use App\Models\ActivityLog;
use App\Models\OfficialLeave;
use Illuminate\Support\Facades\DB;

class LeaveApprovalService
{
    public function getHistory(array $filters, int $perPage = 15)
    {
        $query = OfficialLeave::with(['student.user', 'approver', 'rejecter', 'revoker', 'session.creator']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['class_id'])) {
            $query->where('study_class_id', $filters['class_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function approve(OfficialLeave $leave, int $approvedBy): OfficialLeave
    {
        DB::beginTransaction();

        try {
            $overlap = OfficialLeave::where('student_id', $leave->student_id)
                ->where('status', OfficialLeave::STATUS_APPROVED)
                ->where('id', '!=', $leave->id)
                ->where(function ($q) use ($leave) {
                    $q->whereBetween('start_date', [$leave->start_date, $leave->end_date])
                        ->orWhereBetween('end_date', [$leave->start_date, $leave->end_date])
                        ->orWhere(function ($q2) use ($leave) {
                            $q2->where('start_date', '<=', $leave->start_date)
                                ->where('end_date', '>=', $leave->end_date);
                        });
                })
                ->exists();

            if ($overlap) {
                DB::rollBack();
                throw new \Exception('This student already has an approved leave that overlaps with the selected dates.');
            }

            $before = $leave->toArray();

            $leave->update([
                'status' => OfficialLeave::STATUS_APPROVED,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            ActivityLog::create([
                'user_id' => $approvedBy,
                'action' => 'approved',
                'leave_id' => $leave->id,
                'before' => $before,
                'after' => $leave->fresh()->toArray(),
                'ip_address' => request()->ip(),
            ]);

            DB::commit();

            return $leave->fresh(['student.user', 'approver']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function reject(OfficialLeave $leave, int $rejectedBy, string $note): OfficialLeave
    {
        DB::beginTransaction();

        try {
            $before = $leave->toArray();

            $leave->update([
                'status' => OfficialLeave::STATUS_REJECTED,
                'rejected_by' => $rejectedBy,
                'rejection_note' => $note,
            ]);

            ActivityLog::create([
                'user_id' => $rejectedBy,
                'action' => 'rejected',
                'leave_id' => $leave->id,
                'before' => $before,
                'after' => $leave->fresh()->toArray(),
                'ip_address' => request()->ip(),
            ]);

            DB::commit();

            return $leave->fresh(['student.user', 'rejecter']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function revoke(OfficialLeave $leave, int $revokedBy, string $note): OfficialLeave
    {
        DB::beginTransaction();

        try {
            $before = $leave->toArray();

            $leave->update([
                'status' => OfficialLeave::STATUS_REVOKED,
                'revoked_by' => $revokedBy,
                'revoked_at' => now(),
                'revoked_note' => $note,
            ]);

            ActivityLog::create([
                'user_id' => $revokedBy,
                'action' => 'revoked',
                'leave_id' => $leave->id,
                'before' => $before,
                'after' => $leave->fresh()->toArray(),
                'ip_address' => request()->ip(),
            ]);

            DB::commit();

            return $leave->fresh(['student.user', 'revoker']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(OfficialLeave $leave): bool
    {
        return $leave->forceDelete();
    }
}
