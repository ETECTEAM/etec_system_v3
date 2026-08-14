<?php

namespace App\Modules\Attendance\Queries;

use App\Models\StudentPermission;
use Illuminate\Support\Carbon;

class HasApprovedPermission
{
    /**
     * True if the student has leave covering $date — either for this specific class, or
     * a blanket permission (null study_class_id) covering every class they're enrolled in.
     */
    public function handle(int $studentId, int $studyClassId, Carbon|string $date): bool
    {
        $date = Carbon::parse($date)->toDateString();

        return StudentPermission::query()
            ->where('student_id', $studentId)
            ->where(fn ($query) => $query->whereNull('study_class_id')->orWhere('study_class_id', $studyClassId))
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }
}
