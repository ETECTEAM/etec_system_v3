<?php

namespace App\Modules\AbsenceBlock\Services;

use App\Models\Student;
use App\Models\StudyClass;
use App\Modules\Attendance\Queries\HasApprovedPermission;
use App\Modules\OfficialLeave\Queries\HasApprovedOfficialLeave;
use Carbon\CarbonImmutable;

/**
 * Applies the manual-permission quota. Default 1 permission per ISO week
 * (permission_weekly_limit); an active 'permission' attendance rule's
 * limit_count overrides it. A permission beyond the quota is stored as an
 * absence. Approved official leave / granted permission is always forced through
 * as 'permission' and never counted.
 */
class PermissionLimitEvaluator
{
    public const NOTE_CONVERTED = 'Permission limit exceeded — counted as absence';

    public function __construct(
        private readonly AbsenceRuleMatcher $rules,
        private readonly AbsenceCounter $counter,
        private readonly HasApprovedOfficialLeave $officialLeave,
        private readonly HasApprovedPermission $approvedPermission,
    ) {}

    /**
     * @return array{status: string, note: string|null, counted_as_absence: bool}
     */
    public function resolve(int $studentId, StudyClass $class, CarbonImmutable|string $date): array
    {
        $date = CarbonImmutable::parse($date)->startOfDay();

        if ($this->officialLeave->handle($studentId, $date)
            || $this->approvedPermission->handle($studentId, $class->id, $date)) {
            return ['status' => 'permission', 'note' => null, 'counted_as_absence' => false];
        }

        $student = Student::query()->find($studentId);

        if (! $student || $student->phone === null || $student->phone === '' || ! $class->course_id) {
            return ['status' => 'permission', 'note' => null, 'counted_as_absence' => false];
        }

        $limit = $this->rules->permissionWeeklyLimit($class);

        $used = $this->counter->permissionDays(
            (string) $student->phone,
            (int) $class->course_id,
            $date->startOfWeek(),
            $date->endOfWeek(),
        );

        if ($used >= $limit) {
            return ['status' => 'absent', 'note' => self::NOTE_CONVERTED, 'counted_as_absence' => true];
        }

        return ['status' => 'permission', 'note' => null, 'counted_as_absence' => false];
    }
}
