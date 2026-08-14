<?php

namespace App\Modules\Attendance\Actions;

use App\Models\AttendanceAuditLog;
use App\Models\ClassSession;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Lets an instructor correct a session the system auto-recorded — the only path allowed
 * to change an auto-recorded row (InstructorClassService::saveAttendance rejects a plain
 * re-submit and points here instead, so every post-auto-record edit is audited the same way).
 */
class OverrideAttendanceRecord
{
    public function handle(User $instructor, int $studyClassId, string $sessionDate, array $records): void
    {
        DB::transaction(function () use ($instructor, $studyClassId, $sessionDate, $records): void {
            $session = ClassSession::query()
                ->where('study_class_id', $studyClassId)
                ->whereDate('session_date', $sessionDate)
                ->lockForUpdate()
                ->first();

            if (! $session || $session->status !== ClassSession::STATUS_AUTO_RECORDED) {
                throw ValidationException::withMessages([
                    'records' => 'Only a session the system auto-recorded can be overridden.',
                ]);
            }

            if (! setting('attendance.auto_record_allow_override', true)) {
                throw ValidationException::withMessages([
                    'records' => 'Overriding auto-recorded attendance is currently turned off.',
                ]);
            }

            $overrideHours = (int) setting('attendance.auto_record_override_hours', 24);
            $deadline = Carbon::parse($session->recorded_at)->addHours($overrideHours);

            if (Carbon::now('Asia/Phnom_Penh')->greaterThan($deadline)) {
                throw ValidationException::withMessages([
                    'records' => "The override window closed at {$deadline->format('Y-m-d H:i')}.",
                ]);
            }

            $existingRows = StudentAttendance::query()
                ->where('study_class_id', $studyClassId)
                ->whereDate('attendance_date', $sessionDate)
                ->get()
                ->keyBy('student_enrollment_id');

            foreach ($records as $record) {
                $row = $existingRows->get((int) $record['enrollment_id']);

                // Only ever updates — never inserts. A student with no existing row here
                // wasn't part of what the scheduler auto-recorded (e.g. enrolled after
                // the fact), so there's nothing for an "override" to correct for them.
                if (! $row) {
                    continue;
                }

                $fromStatus = $row->status;
                $fromSource = $row->source;

                $row->update([
                    'status' => $record['status'],
                    'note' => $record['note'] ?? null,
                    'tracked_by' => $instructor->id,
                    'source' => StudentAttendance::SOURCE_MANUAL,
                ]);

                AttendanceAuditLog::create([
                    'student_attendance_id' => $row->id,
                    'changed_by' => $instructor->id,
                    'from_status' => $fromStatus,
                    'to_status' => $record['status'],
                    'from_source' => $fromSource,
                    'to_source' => StudentAttendance::SOURCE_MANUAL,
                ]);
            }

            // recorded_at is left as the original auto-record instant — the banner (part
            // F) shows that timestamp regardless of later corrections. Only the status
            // moves, since the instructor is now the one responsible for it.
            $session->update(['status' => ClassSession::STATUS_RECORDED]);
        });
    }
}
