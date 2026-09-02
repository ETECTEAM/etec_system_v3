<?php

namespace App\Modules\AbsenceBlock\Services;

use App\Models\OfficialLeave;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Counts a person's attendance outcomes across every class of one course,
 * keyed by phone number (the spec's "tel + course_id"). Days already covered by
 * an approved official leave never count.
 */
class AbsenceCounter
{
    /** Distinct absence days for tel+course within [start, end] inclusive. */
    public function absenceDays(string $tel, int $courseId, CarbonInterface $start, CarbonInterface $end): int
    {
        return $this->baseQuery($tel, $courseId, $start, $end, 'absent')
            ->distinct()
            ->count('student_attendances.attendance_date');
    }

    /** Distinct manual-permission days for tel+course within [start, end] inclusive. */
    public function permissionDays(string $tel, int $courseId, CarbonInterface $start, CarbonInterface $end): int
    {
        return $this->baseQuery($tel, $courseId, $start, $end, 'permission')
            ->distinct()
            ->count('student_attendances.attendance_date');
    }

    private function baseQuery(string $tel, int $courseId, CarbonInterface $start, CarbonInterface $end, string $status)
    {
        return DB::table('student_attendances')
            ->join('students', 'students.id', '=', 'student_attendances.student_id')
            ->join('study_classes', 'study_classes.id', '=', 'student_attendances.study_class_id')
            ->where('students.phone', $tel)
            ->where('study_classes.course_id', $courseId)
            ->where('student_attendances.status', $status)
            ->whereBetween('student_attendances.attendance_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('official_leaves')
                    ->whereColumn('official_leaves.student_id', 'student_attendances.student_id')
                    ->where('official_leaves.status', OfficialLeave::STATUS_APPROVED)
                    ->whereColumn('official_leaves.start_date', '<=', 'student_attendances.attendance_date')
                    ->whereColumn('official_leaves.end_date', '>=', 'student_attendances.attendance_date');
            });
    }
}
