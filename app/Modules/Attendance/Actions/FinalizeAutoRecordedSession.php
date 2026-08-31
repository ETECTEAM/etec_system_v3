<?php

namespace App\Modules\Attendance\Actions;

use App\Models\ClassSession;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Modules\AbsenceBlock\Actions\AutoBlockStudent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * After the override window expires, unresolved students are finalized to absent.
 */
class FinalizeAutoRecordedSession
{
    public function handle(int $sessionId): void
    {
        DB::transaction(function () use ($sessionId): void {
            $session = ClassSession::query()->lockForUpdate()->find($sessionId);

            if (! $session || ! in_array($session->status, [
                ClassSession::STATUS_PRE_ATTENDANCE,
                ClassSession::STATUS_PARTIAL,
                ClassSession::STATUS_AUTO_RECORDED,
            ], true) || ! $session->recorded_at) {
                return;
            }

            $overrideHours = (int) setting('attendance.auto_record_override_hours', 24);
            $deadline = $session->recorded_at->copy()->addHours($overrideHours);

            if (Carbon::now('Asia/Phnom_Penh')->lessThanOrEqualTo($deadline)) {
                return;
            }

            $now = Carbon::now('Asia/Phnom_Penh');
            $enrollments = StudentEnrollment::query()
                ->where('study_class_id', $session->study_class_id)
                ->where('enrollment_status', 'active')
                ->get(['id', 'student_id']);

            if ($enrollments->isEmpty()) {
                $session->update(['status' => ClassSession::STATUS_SKIPPED]);

                return;
            }

            $existingRows = StudentAttendance::query()
                ->where('study_class_id', $session->study_class_id)
                ->whereDate('attendance_date', $session->session_date)
                ->whereIn('student_enrollment_id', $enrollments->pluck('id'))
                ->get(['student_enrollment_id', 'status', 'source'])
                ->keyBy('student_enrollment_id');
            $justAbsent = StudentAttendance::query()
                ->where('study_class_id', $session->study_class_id)
                ->whereDate('attendance_date', $session->session_date)
                ->where('source', StudentAttendance::SOURCE_AUTO)
                ->where('status', 'pending')
                ->pluck('student_id');

            StudentAttendance::query()
                ->where('study_class_id', $session->study_class_id)
                ->whereDate('attendance_date', $session->session_date)
                ->where('source', StudentAttendance::SOURCE_AUTO)
                ->where('status', 'pending')
                ->update([
                    'status' => 'absent',
                    'updated_at' => $now,
                ]);

            $missingEnrollments = $enrollments->reject(
                fn (StudentEnrollment $enrollment): bool => $existingRows->has($enrollment->id)
            );

            if ($missingEnrollments->isNotEmpty()) {
                StudentAttendance::query()->insert(
                    $missingEnrollments->map(function (StudentEnrollment $enrollment) use ($session, $now): array {
                        return [
                            'study_class_id' => $session->study_class_id,
                            'student_enrollment_id' => $enrollment->id,
                            'student_id' => $enrollment->student_id,
                            'tracked_by' => null,
                            'attendance_date' => $session->session_date,
                            'status' => 'absent',
                            'source' => StudentAttendance::SOURCE_AUTO,
                            'note' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })->all()
                );
            }

            $session->update(['status' => ClassSession::STATUS_AUTO_RECORDED]);
            // Raise / escalate absence blocks for the students the system just
            // finalized as absent (no instructor involved).
            $autoBlock = app(AutoBlockStudent::class);
            foreach ($justAbsent->unique() as $studentId) {
                $autoBlock->handle((int) $studentId, (int) $session->study_class_id, (string) $session->session_date);
            }
        });
    }
}
