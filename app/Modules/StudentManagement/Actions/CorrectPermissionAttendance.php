<?php

namespace App\Modules\StudentManagement\Actions;

use App\Models\AttendanceAuditLog;
use App\Models\StudentAttendance;
use App\Models\User;
use App\Modules\AbsenceBlock\Actions\AutoBlockStudent;
use App\Modules\AbsenceBlock\Services\AbsenceBlockEvaluator;
use App\Modules\Attendance\Queries\FindActiveInstructorAttendanceBlock;
use App\Modules\Attendance\Queries\HasApprovedPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectPermissionAttendance
{
    public function handle(User $instructor, StudentAttendance $attendance, array $data): void
    {
        DB::transaction(function () use ($instructor, $attendance, $data): void {
            $row = StudentAttendance::query()->lockForUpdate()->findOrFail($attendance->id);
            abort_unless(app(HasApprovedPermission::class)->handle($row->student_id, $row->study_class_id, $row->attendance_date), 403);
            $lock = app(AbsenceBlockEvaluator::class)->evaluate($row->student_id, $row->study_class_id, $row->attendance_date->toDateString());
            if ($row->locked || $lock->locked || app(FindActiveInstructorAttendanceBlock::class)->handle($instructor->id)) {
                throw ValidationException::withMessages(['status' => 'Attendance is blocked from correction.']);
            }
            $fromStatus = $row->statusLabel();
            $fromSource = $row->source;
            $row->update([
                ...StudentAttendance::flagsFor($data['status']),
                'note' => $data['note'] ?? null,
                'source' => StudentAttendance::SOURCE_MANUAL,
                'tracked_by' => $instructor->id,
            ]);
            AttendanceAuditLog::create([
                'student_attendance_id' => $row->id, 'changed_by' => $instructor->id,
                'from_status' => $fromStatus, 'to_status' => $data['status'],
                'from_source' => $fromSource, 'to_source' => StudentAttendance::SOURCE_MANUAL,
            ]);
            if ($data['status'] === StudentAttendance::STATUS_ABSENT) {
                app(AutoBlockStudent::class)->handle($row->student_id, $row->study_class_id, $row->attendance_date->toDateString(), $instructor);
            }
        });
    }
}
