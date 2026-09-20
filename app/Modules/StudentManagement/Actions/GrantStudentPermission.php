<?php

namespace App\Modules\StudentManagement\Actions;

use App\Models\AttendanceAuditLog;
use App\Models\ClassSession;
use App\Models\Holiday;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudentPermission;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GrantStudentPermission
{
    public function handle(Student $student, User $admin, array $data): void
    {
        DB::transaction(function () use ($student, $admin, $data): void {
            $enrollment = StudentEnrollment::query()->with(['studyClass.term', 'studyClass.instructors'])
                ->where('student_id', $student->id)->where('study_class_id', $data['study_class_id'])
                ->where('enrollment_status', 'active')->lockForUpdate()->first();

            if (! $enrollment || ! $enrollment->studyClass) {
                throw ValidationException::withMessages(['reason' => 'This student is not actively enrolled in this class.']);
            }

            $class = $enrollment->studyClass;
            $terms = Term::query()->pluck('term_name', 'id');
            $days = $class->instructors->isEmpty()
                ? StudyClass::parseTermDays($class->term?->term_name)
                : $class->instructors->flatMap(fn ($teacher) => StudyClass::parseTermDays($terms->get($teacher->pivot->term_id) ?? $class->term?->term_name))->unique()->all();
            $holidays = Holiday::query()->whereBetween('date', [$data['start_date'], $data['end_date']])->pluck('date')->map(fn ($date) => Carbon::parse($date)->toDateString())->all();

            StudentPermission::create([...$data, 'student_id' => $student->id, 'approved_by' => $admin->id]);

            for ($date = Carbon::parse($data['start_date']); $date->lte(Carbon::parse($data['end_date'])); $date->addDay()) {
                if (in_array($date->toDateString(), $holidays, true)
                    || ($class->start_date && $date->lt(Carbon::parse($class->start_date)->startOfDay()))
                    || ($class->end_date && $date->gt(Carbon::parse($class->end_date)->startOfDay()))) {
                    continue;
                }

                $session = ClassSession::query()->where('study_class_id', $class->id)->whereDate('session_date', $date)->lockForUpdate()->first();
                if ($session?->status === ClassSession::STATUS_SKIPPED || (! $session && ! in_array($date->format('l'), $days, true))) {
                    continue;
                }

                $row = StudentAttendance::query()->firstOrNew([
                    'study_class_id' => $class->id,
                    'student_enrollment_id' => $enrollment->id,
                    'attendance_date' => $date->toDateString(),
                ]);
                if ($row->locked) {
                    throw ValidationException::withMessages(['reason' => 'Resolve the attendance block before granting permission for these dates.']);
                }
                $fromStatus = $row->exists ? $row->statusLabel() : StudentAttendance::STATUS_PENDING;
                $fromSource = $row->source ?? StudentAttendance::SOURCE_MANUAL;
                $row->fill([
                    'student_id' => $student->id,
                    'tracked_by' => $admin->id,
                    ...StudentAttendance::flagsFor(StudentAttendance::STATUS_PERMISSION),
                    'source' => StudentAttendance::SOURCE_ADMIN_EDIT,
                    'note' => $data['reason'],
                ])->save();
                AttendanceAuditLog::create([
                    'student_attendance_id' => $row->id, 'changed_by' => $admin->id,
                    'from_status' => $fromStatus, 'to_status' => StudentAttendance::STATUS_PERMISSION,
                    'from_source' => $fromSource, 'to_source' => StudentAttendance::SOURCE_ADMIN_EDIT,
                ]);
            }
        });
    }
}
