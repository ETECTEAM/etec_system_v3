<?php

namespace App\Modules\Enroll\Services;

use App\Models\Schedule;
use App\Models\Term;
use Illuminate\Validation\ValidationException;

/**
 * Places an instructor-created class on a term the instructor is actually free for.
 *
 * Instructors may only pick from InstructorClassService::INSTRUCTOR_TERM_NAMES ("Mon & Thu",
 * "Sat & Sun"). A two-part term name is a weekday range, not a pair (StudyClass::parseTermDays),
 * so "Mon & Thu" is Mon-Thu - and a Collapse Class share splits exactly that: the owner takes
 * Mon & Tue, the partner Wed & Thu. Picking "Mon & Thu" again then leaves Monday and Tuesday
 * taken while Wednesday and Thursday are still wide open. The picker offers such a slot rather
 * than hiding it (InstructorAssignmentAvailability::filterScheduleGroups), and this moves the
 * class onto a term covering the days that are left: "Mon & Thu" becomes "Wed & Thu".
 *
 * A replacement has to be a term already configured for this class type and time in Schedule
 * Management - the schedule picker only ever offers configured combinations, so a class must
 * not land on one that was never set up. When nothing qualifies the create is rejected; the
 * class is never silently created on days the instructor cannot teach.
 */
class InstructorClassTermResolver
{
    private const DAY_NAMES = [
        1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
        5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
    ];

    public function __construct(private readonly InstructorAssignmentAvailability $availability) {}

    /**
     * @return array{term_id: int, term_name: string, moved_from: ?string, taken_days: list<string>}
     *
     * @throws ValidationException
     */
    public function resolve(int $userId, int $termId, int $timeId, ?int $classTypeId = null): array
    {
        $pickedName = (string) Term::query()->whereKey($termId)->value('term_name');
        $pickedDays = $this->availability->termDayNumbers($pickedName);

        if ($pickedDays === []) {
            throw ValidationException::withMessages(['term_id' => 'The selected class schedule is invalid.']);
        }

        $freeDays = $this->availability->freeDays($userId, $pickedDays, $timeId);

        // The common case: nothing collides, so the pick stands untouched.
        if (count($freeDays) === count($pickedDays)) {
            return ['term_id' => $termId, 'term_name' => $pickedName, 'moved_from' => null, 'taken_days' => []];
        }

        $takenDays = $this->dayNames(array_values(array_diff($pickedDays, $freeDays)));

        // Not one day of the pick is open - unavailableReason() says precisely why
        // (no availability window, a manual block, or an existing class).
        if ($freeDays === []) {
            throw ValidationException::withMessages([
                'time_id' => $this->availability->unavailableReason($userId, $termId, $timeId)
                    ?? 'The selected instructor already has a class at this time.',
            ]);
        }

        $replacement = $this->freeTermFor($userId, $freeDays, $pickedDays, $termId, $timeId, $classTypeId);

        if ($replacement === null) {
            throw ValidationException::withMessages([
                'term_id' => 'You already teach on '.$this->listDays($takenDays)
                    .' at this time, and no other schedule is free for this class type and time.',
            ]);
        }

        return [
            'term_id' => (int) $replacement->id,
            'term_name' => (string) $replacement->term_name,
            'moved_from' => $pickedName,
            'taken_days' => $takenDays,
        ];
    }

    /**
     * The best configured term to move onto: it must keep at least one day the instructor picked
     * and is still free on, and be free end to end - moving off one conflict and onto another is
     * no help. Ranked by days kept from the pick, then by staying closest to the picked term's
     * weekly load (so a 2-day pick prefers a 2-day replacement), then by id so the choice is
     * deterministic rather than dependent on row order.
     *
     * @param  list<int>  $freeDays
     * @param  list<int>  $pickedDays
     */
    private function freeTermFor(int $userId, array $freeDays, array $pickedDays, int $pickedTermId, int $timeId, ?int $classTypeId): ?Term
    {
        $termIds = Schedule::query()
            ->when($classTypeId !== null, fn ($query) => $query->where('class_type_id', $classTypeId))
            ->forTime($timeId)
            ->pluck('term_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->reject(fn (int $id): bool => $id === $pickedTermId)
            ->values();

        if ($termIds->isEmpty()) {
            return null;
        }

        $best = Term::query()
            ->whereIn('id', $termIds->all())
            ->get(['id', 'term_name'])
            ->map(function (Term $term) use ($userId, $freeDays, $pickedDays, $timeId): ?array {
                $days = $this->availability->termDayNumbers($term->term_name);
                $kept = array_intersect($days, $freeDays);

                if ($days === [] || $kept === []) {
                    return null;
                }

                if (count($this->availability->freeDays($userId, $days, $timeId)) !== count($days)) {
                    return null;
                }

                return [
                    'term' => $term,
                    'kept' => count($kept),
                    'delta' => abs(count($days) - count($pickedDays)),
                    'id' => (int) $term->id,
                ];
            })
            ->filter()
            ->sort(fn (array $a, array $b): int => [$b['kept'], $a['delta'], $a['id']] <=> [$a['kept'], $b['delta'], $b['id']])
            ->first();

        return $best['term'] ?? null;
    }

    /**
     * @param  list<int>  $days
     * @return list<string>
     */
    private function dayNames(array $days): array
    {
        sort($days);

        return array_values(array_filter(array_map(fn (int $day): ?string => self::DAY_NAMES[$day] ?? null, $days)));
    }

    /**
     * @param  list<string>  $days
     */
    private function listDays(array $days): string
    {
        if (count($days) < 2) {
            return $days[0] ?? 'those days';
        }

        return implode(', ', array_slice($days, 0, -1)).' and '.end($days);
    }
}
