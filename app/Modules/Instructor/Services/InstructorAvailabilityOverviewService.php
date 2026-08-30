<?php

namespace App\Modules\Instructor\Services;

use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Models\StudyClass;
use App\Models\Time;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;
use Illuminate\Support\Collection;

/**
 * Builds an admin-facing weekly availability grid across every instructor.
 * Because every class is booked against a time slot (the `times` table), the
 * grid is Instructors x Weekdays x TimeSlots: for each active instructor and
 * each day, every time slot is classified as free or busy. A slot counts as
 * busy when it overlaps an assigned open class or one of the instructor's own
 * manual schedule blocks (same half-open interval rule the class-assignment
 * guards use), so the admin sees both the free and the busy times at a glance.
 */
class InstructorAvailabilityOverviewService
{
    public const DAY_LABELS = [
        1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu',
        5 => 'Fri', 6 => 'Sat', 7 => 'Sun',
    ];

    public function __construct(private InstructorAssignmentAvailability $instructorAvailability)
    {
    }

    /**
     * The time-slot columns shared by every instructor row. Ordered by start
     * time so the grid reads chronologically, matching the class-form picker.
     *
     * @return array<int, array{id: int, time_name: string, start: ?string, end: ?string}>
     */
    public function timeSlots(): array
    {
        return Time::query()
            ->orderBy('time_name')
            ->get(['id', 'time_name'])
            ->map(fn (Time $time): array => [
                'id' => $time->id,
                'time_name' => $time->time_name,
                'start' => StudyClass::parseTimeRange($time->time_name)['start'] ?? null,
                'end' => StudyClass::parseTimeRange($time->time_name)['end'] ?? null,
            ])
            ->filter(fn (array $slot): bool => $slot['start'] !== null && $slot['end'] !== null)
            ->values()
            ->all();
    }

    /**
     * @return array{slots: array<int, array<string, mixed>>, instructors: array<int, array<string, mixed>>}
     */
    public function overview(): array
    {
        $slots = $this->timeSlots();

        $instructors = InstructorData::query()
            ->where('status', true)
            ->whereHas('user', fn ($query) => $query->where('status', 'active')->role('instructor'))
            ->with(['user:id,name,email'])
            ->orderBy('full_name')
            ->get()
            ->map(fn (InstructorData $instructor): array => $this->presentInstructor($instructor, $slots))
            ->values()
            ->all();

        return [
            'slots' => $slots,
            'instructors' => $instructors,
        ];
    }

    /**
     * @param  array<int, array{id: int, time_name: string, start: ?string, end: ?string}>  $slots
     * @return array<string, mixed>
     */
    private function presentInstructor(InstructorData $instructor, array $slots): array
    {
        $occupied = $this->instructorAvailability->occupiedSlots($instructor->user_id);

        $blocks = InstructorScheduleBlock::query()
            ->where('instructor_id', $instructor->id)
            ->where('status', InstructorScheduleBlock::STATUS_ACTIVE)
            ->with('time:id,time_name')
            ->get();

        $occupiedByDay = $occupied->groupBy('day_of_week');
        $blocksByDay = $blocks->groupBy('day_of_week');

        $days = collect(range(1, 7))->map(function (int $day) use ($slots, $occupiedByDay, $blocksByDay): array {
            $dayOccupied = $occupiedByDay->get($day, collect());
            $dayBlocks = $blocksByDay->get($day, collect());

            $daySlots = [];
            $isFree = true;

            foreach ($slots as $slot) {
                $start = $slot['start'];
                $end = $slot['end'];

                // A slot is "class busy" when it overlaps an assigned open class
                // on that day (same range-overlap rule, not an exact time_id match).
                $class = $dayOccupied->first(
                    fn (array $o): bool => $start < $o['end'] && $end > $o['start']
                );

                $block = $class === null
                    ? $dayBlocks->first(function (InstructorScheduleBlock $b) use ($start, $end): bool {
                        $range = StudyClass::parseTimeRange($b->time?->time_name);

                        return $range['start'] !== null && $range['end'] !== null
                            && $start < $range['end'] && $end > $range['start'];
                    })
                    : null;

                $status = match (true) {
                    $class !== null => 'class',
                    $block !== null => 'block',
                    default => 'free',
                };

                if ($status !== 'free') {
                    $isFree = false;
                }

                $daySlots[] = [
                    'time_id' => $slot['id'],
                    'time_name' => $slot['time_name'],
                    'status' => $status,
                    'title' => $class !== null
                        ? $class['title']
                        : ($block?->reason),
                    'class_id' => $class['class_id'] ?? null,
                    'block_id' => $block?->id,
                ];
            }

            return [
                'day_of_week' => $day,
                'day_label' => self::DAY_LABELS[$day] ?? (string) $day,
                'is_free' => $isFree,
                'slots' => $daySlots,
            ];
        })->values();

        return [
            'id' => $instructor->id,
            'user_id' => $instructor->user_id,
            'full_name' => $instructor->full_name,
            'email' => $instructor->user?->email,
            'available_for_class' => (bool) $instructor->available_for_class,
            'employment_type' => $instructor->employment_type,
            'days' => $days,
        ];
    }
}
