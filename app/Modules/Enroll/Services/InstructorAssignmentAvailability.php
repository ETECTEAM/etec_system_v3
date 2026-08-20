<?php

namespace App\Modules\Enroll\Services;

use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;

class InstructorAssignmentAvailability
{
    private const OPEN_CLASS_STATUSES = ['upcoming', 'active', 'pre_end'];

    /**
     * Returns a validation error when this instructor cannot teach the given
     * term/time, or null when the assignment is safe.
     */
    public function unavailableReason(int $userId, int $termId, int $timeId, ?int $exceptClassId = null): ?string
    {
        $instructor = InstructorData::query()
            ->with(['availabilities' => fn ($query) => $query->where('is_active', true)])
            ->where('user_id', $userId)
            ->where('available_for_class', true)
            ->where('status', true)
            ->whereHas('user', fn ($query) => $query->where('status', 'active')->role('instructor'))
            ->first();

        if ($instructor === null) {
            return 'The selected instructor is not available for class assignment.';
        }

        $days = $this->termDays($termId);
        $range = StudyClass::parseTimeRange(Time::query()->whereKey($timeId)->value('time_name'));

        if ($days === [] || $range['start'] === null || $range['end'] === null) {
            return 'The selected class schedule is invalid.';
        }

        foreach ($days as $day) {
            $coversSlot = $instructor->availabilities->contains(
                fn ($availability): bool => (int) $availability->day_of_week === $day
                    && substr((string) $availability->start_time, 0, 5) <= $range['start']
                    && substr((string) $availability->end_time, 0, 5) >= $range['end']
            );

            if (! $coversSlot) {
                return 'The selected instructor is not available for this class schedule.';
            }
        }

        $hasManualBlock = InstructorScheduleBlock::query()
            ->where('instructor_id', $instructor->id)
            ->where('time_id', $timeId)
            ->whereIn('day_of_week', $days)
            ->where('status', InstructorScheduleBlock::STATUS_ACTIVE)
            ->exists();

        if ($hasManualBlock) {
            return 'The selected instructor has blocked this class schedule.';
        }

        $hasClassConflict = StudyClass::query()
            ->where('teacher_id', $userId)
            ->where('term_id', $termId)
            ->where('time_id', $timeId)
            ->whereIn('status', self::OPEN_CLASS_STATUSES)
            ->when($exceptClassId !== null, fn ($query) => $query->where('id', '!=', $exceptClassId))
            ->exists();

        return $hasClassConflict
            ? 'The selected instructor already has a class at this time.'
            : null;
    }

    private function termDays(int $termId): array
    {
        $termName = Term::query()->whereKey($termId)->value('term_name');
        $dayNumbers = [
            'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4,
            'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7,
        ];

        return collect(StudyClass::parseTermDays($termName))
            ->map(fn (string $day): ?int => $dayNumbers[$day] ?? null)
            ->filter()
            ->values()
            ->all();
    }
}
