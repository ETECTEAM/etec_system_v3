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
        $instructor = $this->loadAssignableInstructor($userId);

        if ($instructor === null) {
            return 'The selected instructor is not available for class assignment.';
        }

        $days = $this->termDays($termId);
        $range = StudyClass::parseTimeRange(Time::query()->whereKey($timeId)->value('time_name'));

        if ($days === [] || $range['start'] === null || $range['end'] === null) {
            return 'The selected class schedule is invalid.';
        }

        if (! $this->windowCoversSlot($instructor, $days, $range)) {
            return 'The selected instructor is not available for this class schedule.';
        }

        // Not an exact time_id match: a block on 03:30-05:00 must also cover a
        // 03:30-05:30 assignment even though they're different Time records.
        $activeBlocks = InstructorScheduleBlock::query()
            ->where('instructor_id', $instructor->id)
            ->where('status', InstructorScheduleBlock::STATUS_ACTIVE)
            ->with('time:id,time_name')
            ->get();

        if ($this->manualBlockOverlapsSlot($activeBlocks, $days, $range)) {
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

        $classes = $this->loadInstructorOpenClasses($userId, $exceptClassId);

        if ($classes->isEmpty()) {
            return false;
        }

        $termNames = $this->termNamesFor($classes);

        return $classes->contains(
            fn (StudyClass $class): bool => $this->classOverlapsSlot($userId, $class, $days, $range, $termNames)
        );
    }

    /**
     * Narrows scheduleGroups (from GetClassFormOptions::scheduleGroups()) to only the
     * term/time slots this instructor can be assigned to teach: covered by an active
     * availability window, not manually blocked, and not overlapping a class they
     * already teach. Schedules and groups left with no times are dropped, so the
     * class-create form only ever offers a slot the instructor is actually free for.
     * $exceptClassId excludes one class from the overlap check (for the edit form).
     *
     * @param  array<int, array<string, mixed>>  $groups
     * @return array<int, array<string, mixed>>
     */
    public function filterScheduleGroups(int $userId, array $groups, ?int $exceptClassId = null): array
    {
        $instructor = $this->loadAssignableInstructor($userId);

        if ($instructor === null) {
            return [];
        }

        $activeBlocks = InstructorScheduleBlock::query()
            ->where('instructor_id', $instructor->id)
            ->where('status', InstructorScheduleBlock::STATUS_ACTIVE)
            ->with('time:id,time_name')
            ->get();

        $classes = $this->loadInstructorOpenClasses($userId, $exceptClassId);
        $termNames = $this->termNamesFor($classes);

        return collect($groups)
            ->map(function ($group) use ($instructor, $activeBlocks, $classes, $termNames, $userId): array {
                $schedules = collect($group['schedules'] ?? [])
                    ->map(function ($schedule) use ($instructor, $activeBlocks, $classes, $termNames, $userId): array {
                        $days = $this->dayNumbers($schedule['term_name'] ?? null);

                        $times = collect($schedule['times'] ?? [])
                            ->filter(function ($time) use ($instructor, $activeBlocks, $classes, $termNames, $userId, $days): bool {
                                if ($days === []) {
                                    return false;
                                }

                                $range = StudyClass::parseTimeRange($time['time_name'] ?? null);

                                if ($range['start'] === null || $range['end'] === null) {
                                    return false;
                                }

                                return $this->windowCoversSlot($instructor, $days, $range)
                                    && ! $this->manualBlockOverlapsSlot($activeBlocks, $days, $range)
                                    && ! $classes->contains(
                                        fn (StudyClass $class): bool => $this->classOverlapsSlot($userId, $class, $days, $range, $termNames)
                                    );
                            })
                            ->values()
                            ->all();

                        return [
                            'term_id' => $schedule['term_id'] ?? null,
                            'term_name' => $schedule['term_name'] ?? '-',
                            'times' => $times,
                        ];
                    })
                    ->filter(fn (array $schedule): bool => $schedule['times'] !== [])
                    ->values()
                    ->all();

                return [
                    'class_type_id' => $group['class_type_id'] ?? null,
                    'class_type_name' => $group['class_type_name'] ?? '-',
                    'schedules' => $schedules,
                ];
            })
            ->filter(fn (array $group): bool => $group['schedules'] !== [])
            ->values()
            ->all();
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

    private function loadAssignableInstructor(int $userId): ?InstructorData
    {
        return InstructorData::query()
            ->with(['availabilities' => fn ($query) => $query->where('is_active', true)])
            ->where('user_id', $userId)
            ->where('available_for_class', true)
            ->where('status', true)
            ->whereHas('user', fn ($query) => $query->where('status', 'active')->role('instructor'))
            ->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, StudyClass>
     */
    private function loadInstructorOpenClasses(int $userId, ?int $exceptClassId): Collection
    {
        return StudyClass::query()
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
    }

    /**
     * @param  array{start: ?string, end: ?string}  $range
     */
    private function windowCoversSlot(InstructorData $instructor, array $days, array $range): bool
    {
        foreach ($days as $day) {
            $coversSlot = $instructor->availabilities->contains(
                fn ($availability): bool => (int) $availability->day_of_week === $day
                    && substr((string) $availability->start_time, 0, 5) <= $range['start']
                    && substr((string) $availability->end_time, 0, 5) >= $range['end']
            );

            if (! $coversSlot) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, InstructorScheduleBlock>  $activeBlocks
     * @param  array{start: ?string, end: ?string}  $range
     */
    private function manualBlockOverlapsSlot(Collection $activeBlocks, array $days, array $range): bool
    {
        return $activeBlocks->contains(function (InstructorScheduleBlock $block) use ($days, $range): bool {
            if (! in_array((int) $block->day_of_week, $days, true)) {
                return false;
            }

            $blockRange = StudyClass::parseTimeRange($block->time?->time_name);

            return $blockRange['start'] !== null && $blockRange['end'] !== null
                && $range['start'] < $blockRange['end'] && $range['end'] > $blockRange['start'];
        });
    }

    /**
     * @param  array{start: ?string, end: ?string}  $range
     * @param  Collection<int, string>  $termNames
     */
    private function classOverlapsSlot(int $userId, StudyClass $class, array $days, array $range, Collection $termNames): bool
    {
        $classDays = $this->instructorDaysInClass($userId, $class, $termNames);

        if (array_intersect($days, $classDays) === []) {
            return false;
        }

        $classRange = StudyClass::parseTimeRange($class->time?->time_name);

        if ($classRange['start'] === null || $classRange['end'] === null) {
            return false;
        }

        return $range['start'] < $classRange['end'] && $range['end'] > $classRange['start'];
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
