<?php

namespace App\Modules\Instructor\Services;

use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Models\StudyClass;
use App\Models\Time;
use App\Models\WorkScheduleTime;
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
 *
 * Each slot also carries what an admin needs to act on it from the grid:
 *  - `block_id` / `blocked_by` — the manual block covering it, and whether the
 *    instructor set it themselves ('instructor') or an admin did ('admin').
 *  - `availability_id` / `availability_source` — the availability window it
 *    falls in, and whether that window came from the work schedule ('schedule')
 *    or was opened by an admin ('admin').
 */
class InstructorAvailabilityOverviewService
{
    public const DAY_LABELS = [
        1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu',
        5 => 'Fri', 6 => 'Sat', 7 => 'Sun',
    ];

    public function __construct(private InstructorAssignmentAvailability $instructorAvailability) {}

    /**
     * The time-slot rows for each day of the week, sourced from the work
     * schedules themselves (work_schedule_times) so weekdays and weekends get
     * their own granularity - weekends run wide blocks ("08:00 am - 11:00 am",
     * "11:00 am - 01:30 pm", "02:00 pm - 05:00 pm") while weekdays run the
     * 90-minute class slots. A day with no work schedule (Friday here) gets
     * an empty list.
     *
     * Within a day the `times` table still has overlapping records because
     * different class types slice it differently ("09:00 am - 10:30 am" vs
     * "09:00 am - 11:00 am", plus the odd malformed duplicate), so collapse
     * to one row per start time keeping the shortest range - it still fits
     * inside a working window, and class / block busy-state is decided by
     * range overlap (not an exact time_id) so a longer booking at the same
     * start still marks the row busy.
     *
     * @return array<int, list<array{id: int, time_name: string, start: string, end: string}>>
     */
    public function timeSlotsByDay(): array
    {
        $times = Time::query()
            ->get(['id', 'time_name'])
            ->mapWithKeys(fn (Time $time): array => [$time->id => [
                'id' => $time->id,
                'time_name' => $time->time_name,
                'start' => StudyClass::parseTimeRange($time->time_name)['start'] ?? null,
                'end' => StudyClass::parseTimeRange($time->time_name)['end'] ?? null,
            ]]);

        $timeIdsByDay = WorkScheduleTime::query()
            ->select('day_of_week', 'time_id')
            ->distinct()
            ->get()
            ->groupBy('day_of_week');

        return collect(range(1, 7))
            ->mapWithKeys(fn (int $day): array => [$day => $timeIdsByDay->get($day, collect())
                ->map(fn (WorkScheduleTime $row): ?array => $times->get($row->time_id))
                ->filter(fn (?array $slot): bool => $slot !== null && $slot['start'] !== null && $slot['end'] !== null)
                ->sortBy([['start', 'asc'], ['end', 'asc']])
                ->groupBy('start')
                ->map(fn (Collection $group): array => $group->first())
                ->values()
                ->all(),
            ])
            ->all();
    }

    /**
     * @return array{instructors: array<int, array<string, mixed>>}
     */
    public function overview(): array
    {
        $slotsByDay = $this->timeSlotsByDay();

        $instructors = InstructorData::query()
            ->where('status', true)
            ->whereHas('user', fn ($query) => $query->where('status', 'active')->role('instructor'))
            ->with(['user:id,name,email'])
            ->orderBy('full_name')
            ->get()
            ->map(fn (InstructorData $instructor): array => $this->presentInstructor($instructor, $slotsByDay))
            ->values()
            ->all();

        return [
            'instructors' => $instructors,
        ];
    }

    /**
     * @param  array<int, list<array{id: int, time_name: string, start: string, end: string}>>  $slotsByDay
     * @return array<string, mixed>
     */
    private function presentInstructor(InstructorData $instructor, array $slotsByDay): array
    {
        $occupied = $this->instructorAvailability->occupiedSlots($instructor->user_id);

        $blocks = InstructorScheduleBlock::query()
            ->where('instructor_id', $instructor->id)
            ->where('status', InstructorScheduleBlock::STATUS_ACTIVE)
            ->with(['time:id,time_name', 'creator:id,name'])
            ->get();

        // Active working windows: schedule-derived + any an admin opened manually.
        $availabilityWindows = $instructor->availabilities()
            ->where('is_active', true)
            ->get(['id', 'day_of_week', 'start_time', 'end_time', 'source'])
            ->groupBy('day_of_week');

        $occupiedByDay = $occupied->groupBy('day_of_week');
        $blocksByDay = $blocks->groupBy('day_of_week');

        $days = collect(range(1, 7))->map(function (int $day) use ($slotsByDay, $occupiedByDay, $blocksByDay, $availabilityWindows): array {
            $dayOccupied = $occupiedByDay->get($day, collect());
            $dayBlocks = $blocksByDay->get($day, collect());
            $dayWindows = $availabilityWindows->get($day, collect());

            $daySlots = [];

            foreach ($slotsByDay[$day] ?? [] as $slot) {
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

                $window = $class === null && $block === null
                    ? $dayWindows->first(
                        fn ($w): bool => substr((string) $w->start_time, 0, 5) <= $start
                            && substr((string) $w->end_time, 0, 5) >= $end
                    )
                    : null;

                $status = match (true) {
                    $class !== null => 'class',
                    $block !== null => 'block',
                    $window !== null => 'available',
                    default => 'not_working',
                };

                $daySlots[] = [
                    'time_id' => $slot['id'],
                    'time_name' => $slot['time_name'],
                    'status' => $status,
                    'title' => $class !== null
                        ? $class['title']
                        : ($block?->reason),
                    'class_id' => $class['class_id'] ?? null,
                    'block_id' => $block?->id,
                    'blocked_by' => $block === null
                        ? null
                        : ($block->created_by ? 'admin' : 'instructor'),
                    'blocked_by_name' => $block?->creator?->name,
                    'availability_id' => $window?->id,
                    'availability_source' => $window?->source,
                ];
            }

            return [
                'day_of_week' => $day,
                'day_label' => self::DAY_LABELS[$day] ?? (string) $day,
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
