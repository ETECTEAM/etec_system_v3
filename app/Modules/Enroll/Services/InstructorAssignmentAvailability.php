<?php

namespace App\Modules\Enroll\Services;

use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InstructorAssignmentAvailability
{
    private const OPEN_CLASS_STATUSES = ['upcoming', 'active', 'pre_end'];

    private const DAY_NUMBERS = [
        'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4,
        'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7,
    ];

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

        if ($this->hasConflictingClass($userId, $days, $timeId, $exceptClassId)) {
            return 'The selected instructor already has a class at this time.';
        }

        return null;
    }

    /**
     * Whether this instructor already teaches another open class on any of the given
     * weekdays at an overlapping time. The conflict days come from the class's own
     * term for an unshared class, or from the instructor's study_class_instructors
     * pivot term for a shared/collapsed class — so "Mon & Thu" and "Thu & Fri"
     * correctly collide on Thursday even though their term_ids differ, and shared
     * instructors are counted too.
     *
     * Not filtered by an exact time_id match: two different Time records (9:00-10:30
     * and 9:00-11:00) share no time_id but do overlap, so every one of the
     * instructor's open classes is checked against the actual [start, end) range.
     */
    public function hasConflictingClass(int $userId, array $days, int $timeId, ?int $exceptClassId = null): bool
    {
        if ($days === []) {
            return false;
        }

        $range = StudyClass::parseTimeRange(Time::query()->whereKey($timeId)->value('time_name'));

        if ($range['start'] === null || $range['end'] === null) {
            return false;
        }

        $classes = StudyClass::query()
            ->whereIn('status', self::OPEN_CLASS_STATUSES)
            ->where(fn (Builder $query) => $query
                ->where('teacher_id', $userId)
                ->orWhereHas('instructors', fn ($query) => $query->where('users.id', $userId)))
            ->when($exceptClassId !== null, fn (Builder $query) => $query->where('id', '!=', $exceptClassId))
            ->with([
                'term:id,term_name',
                'time:id,time_name',
                'instructors' => fn ($query) => $query->where('users.id', $userId)->select('users.id'),
            ])
            ->get();

        if ($classes->isEmpty()) {
            return false;
        }

        $termNames = $this->termNamesFor($classes);

        foreach ($classes as $class) {
            $classDays = $this->instructorDaysInClass($userId, $class, $termNames);

            if (array_intersect($days, $classDays) === []) {
                continue;
            }

            $classRange = StudyClass::parseTimeRange($class->time?->time_name);

            if ($classRange['start'] === null || $classRange['end'] === null) {
                continue;
            }

            if ($range['start'] < $classRange['end'] && $range['end'] > $classRange['start']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every (day_of_week, start, end) slot this instructor is already teaching an open
     * class in, across the whole week - used to mark those slots "occupied" on the
     * instructor's own availability calendar instead of showing them as plain
     * "available" just because they fall inside a configured working window.
     *
     * Carries the class's own parsed start/end rather than just its time_id: the
     * caller must flag a candidate slot occupied whenever its time range actually
     * overlaps this one, even when the two belong to different Time records (a
     * 9:00-10:30 class and a 9:00-11:00 class share no time_id but do overlap).
     */
    public function occupiedSlots(int $userId): Collection
    {
        $classes = StudyClass::query()
            ->whereIn('status', self::OPEN_CLASS_STATUSES)
            ->where(fn (Builder $query) => $query
                ->where('teacher_id', $userId)
                ->orWhereHas('instructors', fn ($query) => $query->where('users.id', $userId)))
            ->with([
                'term:id,term_name',
                'time:id,time_name',
                'instructors' => fn ($query) => $query->where('users.id', $userId)->select('users.id'),
            ])
            ->get(['id', 'title', 'term_id', 'time_id', 'teacher_id', 'status']);

        if ($classes->isEmpty()) {
            return collect();
        }

        $termNames = $this->termNamesFor($classes);

        return $classes
            ->flatMap(function (StudyClass $class) use ($userId, $termNames): array {
                $range = StudyClass::parseTimeRange($class->time?->time_name);

                if ($range['start'] === null || $range['end'] === null) {
                    return [];
                }

                return collect($this->instructorDaysInClass($userId, $class, $termNames))
                    ->map(fn (int $day): array => [
                        'day_of_week' => $day,
                        'time_id' => $class->time_id,
                        'start' => $range['start'],
                        'end' => $range['end'],
                        'class_id' => $class->id,
                        'title' => $class->title,
                    ])
                    ->all();
            })
            ->values();
    }

    private function instructorDaysInClass(int $userId, StudyClass $class, Collection $termNames): array
    {
        foreach ($class->instructors as $instructor) {
            if ((int) $instructor->id === $userId) {
                $termName = $instructor->pivot->term_id !== null
                    ? $termNames->get($instructor->pivot->term_id)
                    : $class->term?->term_name;

                return $this->dayNumbers($termName);
            }
        }

        return $this->dayNumbers($class->term?->term_name);
    }

    private function termNamesFor(Collection $classes): Collection
    {
        $termIds = $classes->flatMap(fn (StudyClass $class): array => array_merge(
            [$class->term_id],
            $class->instructors->pluck('pivot.term_id')->filter()->all(),
        ))->unique()->values()->all();

        return Term::query()->whereIn('id', $termIds)->pluck('term_name', 'id');
    }

    private function termDays(int $termId): array
    {
        return $this->dayNumbers(Term::query()->whereKey($termId)->value('term_name'));
    }

    private function dayNumbers(?string $termName): array
    {
        return collect(StudyClass::parseTermDays($termName))
            ->map(fn (string $day): ?int => self::DAY_NUMBERS[$day] ?? null)
            ->filter()
            ->values()
            ->all();
    }
}