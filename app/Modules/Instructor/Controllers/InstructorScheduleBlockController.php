<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InstructorScheduleBlock;
use App\Models\StudyClass;
use App\Models\Time;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;
use App\Support\InstructorDisplayName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InstructorScheduleBlockController extends Controller
{
    private const DAY_LABELS = [
        1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
        5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
    ];

    public function index(Request $request): Response
    {
        $instructorData = $request->user()->instructorData()
            ->with(['workSchedule'])
            ->first();

        abort_unless($instructorData, 404, 'No instructor profile found for this account.');

        return Inertia::render('backend/instructors/ScheduleBlocks', [
            'instructorName' => InstructorDisplayName::format($instructorData->full_name),
            'workSchedule' => $instructorData->workSchedule
                ? ['name' => $instructorData->workSchedule->name]
                : null,
        ]);
    }

    /**
     * Returns a 7-day calendar structure. Every day of the week is always
     * present so the frontend can render a fixed 7-column calendar. Days
     * where the instructor has no WorkScheduleTime entries are marked as
     * non-working. For working days, only Time records that are linked to
     * the instructor's WorkSchedule for that day are shown.
     */
    public function data(Request $request, InstructorAssignmentAvailability $instructorAvailability): JsonResponse
    {
        $instructorData = $request->user()->instructorData()->first();

        abort_unless($instructorData, 404, 'No instructor profile found for this account.');

        $availabilities = $instructorData->availabilities()->where('is_active', true)->get(['day_of_week', 'start_time', 'end_time']);
        $blocks = $instructorData->scheduleBlocks()
            ->where('status', InstructorScheduleBlock::STATUS_ACTIVE)
            ->with('time:id,time_name')
            ->get(['id', 'day_of_week', 'time_id', 'reason']);
        $blocksByDay = $blocks->groupBy('day_of_week');
        // Grouped by day, not keyed by "day:time_id": an occupying class can be booked
        // under a different Time record than the slot being checked (9:00-10:30 vs
        // 9:00-11:00 share no time_id) and still overlap it, so every slot on a day
        // has to be checked against all of that day's occupied ranges, not just one
        // with a matching time_id.
        $occupiedByDay = $instructorAvailability->occupiedSlots($instructorData->user_id)
            ->groupBy('day_of_week');

        $workingDays = $availabilities->pluck('day_of_week')->unique()->sort()->values()->all();
        $dayWindows = $availabilities->groupBy('day_of_week')
            ->map(fn ($slots) => $slots->map(fn ($s) => [
                'start' => substr($s->start_time, 0, 5),
                'end' => substr($s->end_time, 0, 5),
            ])->values());

        // Load WorkScheduleTime records with their Time relation.
        $workScheduleTimes = $instructorData->workSchedule
            ? $instructorData->workSchedule->times()->with('time:id,time_name')->get()
            : collect();

        // The schedule times were loaded with their Time relation above, so
        // keep that exact ID-to-model mapping. Rebuilding it through a flipped
        // collection loses IDs and leaves later slots (such as weekend slots)
        // without their time range.
        $timeModels = $workScheduleTimes
            ->filter(fn ($wst) => $wst->time)
            ->mapWithKeys(fn ($wst) => [$wst->time_id => $wst->time]);

        // Group by day_of_week, deduplicate by time_name per day.
        $dayTimeIds = $workScheduleTimes->groupBy('day_of_week')
            ->map(fn ($entries) => $entries
                ->map(fn ($wst) => [
                    'time_id' => $wst->time_id,
                    'time_name' => $wst->time?->time_name ?? '',
                ])
                ->unique('time_name')
                ->pluck('time_id')
                ->values()
            );

        $schedule = collect(range(1, 7))->map(function (int $day) use ($dayWindows, $workingDays, $dayTimeIds, $timeModels, $occupiedByDay, $blocksByDay): array {
            $isWorking = in_array($day, $workingDays);

            // Only Time records assigned to THIS day via WorkScheduleTime.
            $dayTimeEntries = $dayTimeIds->get($day, collect());
            $dayOccupied = $occupiedByDay->get($day, collect());
            $dayBlocks = $blocksByDay->get($day, collect());

            $slots = $dayTimeEntries->map(function (int $timeId) use ($day, $isWorking, $dayWindows, $timeModels, $dayOccupied, $dayBlocks): array {
                $time = $timeModels->get($timeId);
                $timeName = $time?->time_name ?? '';

                $range = StudyClass::parseTimeRange($timeName);
                $start = $range['start'] ?? null;
                $end = $range['end'] ?? null;

                $isAvailable = false;
                if ($isWorking && $start !== null && $end !== null) {
                    $isAvailable = ($dayWindows[$day] ?? collect())->contains(
                        fn (array $w): bool => $w['start'] <= $start && $w['end'] >= $end
                    );
                }

                // Same overlap rule as occupied classes below, not an exact time_id
                // match - a block on 03:30-05:00 must also cover a 03:30-05:30 slot
                // even though they're different Time records.
                $block = ($start !== null && $end !== null)
                    ? $dayBlocks->first(function (InstructorScheduleBlock $b) use ($start, $end): bool {
                        $blockRange = StudyClass::parseTimeRange($b->time?->time_name);

                        return $blockRange['start'] !== null && $blockRange['end'] !== null
                            && $start < $blockRange['end'] && $end > $blockRange['start'];
                    })
                    : null;

                // Any actual overlap with an already-assigned class occupies this slot,
                // not just an exact time_id match - a 9:00-11:00 slot is occupied by a
                // class booked 9:00-10:30 even though they're different Time records.
                // Touching boundaries (10:30-12:00 next to a 9:00-10:30 class) don't
                // count, hence strict < / > rather than <= / >=.
                $class = ($start !== null && $end !== null)
                    ? $dayOccupied->first(fn (array $o): bool => $start < $o['end'] && $end > $o['start'])
                    : null;

                // A slot the instructor is already teaching an open class in is
                // "occupied", not plain "available" - that state wins over a
                // manual block too, since the block is moot once a class is
                // actually assigned there.
                $status = match (true) {
                    $class !== null => 'occupied',
                    $block !== null => 'blocked',
                    $isAvailable => 'available',
                    default => 'not_working',
                };

                return [
                    'time_id' => $timeId,
                    'time_name' => $timeName,
                    'status' => $status,
                    'block_id' => $block?->id,
                    'reason' => $block?->reason,
                    'class_id' => $class['class_id'] ?? null,
                    'class_title' => $class['title'] ?? null,
                ];
            })->values();

            return [
                'day_of_week' => $day,
                'day_label' => self::DAY_LABELS[$day] ?? (string) $day,
                'is_working' => $isWorking,
                'shift_windows' => $dayWindows[$day] ?? [],
                'slots' => $slots,
            ];
        })->values();

        return response()->json(['schedule' => $schedule]);
    }

    public function store(Request $request): JsonResponse
    {
        $instructorData = $request->user()->instructorData()->first();

        abort_unless($instructorData, 404, 'No instructor profile found for this account.');

        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'time_id' => ['required', 'integer', 'exists:times,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $time = Time::query()->findOrFail($validated['time_id']);
        $range = StudyClass::parseTimeRange($time->time_name);
        $start = $range['start'] ?? null;
        $end = $range['end'] ?? null;

        $isWorkingSlot = $start !== null && $end !== null && $instructorData->availabilities()
            ->where('is_active', true)
            ->where('day_of_week', $validated['day_of_week'])
            ->where('start_time', '<=', $start)
            ->where('end_time', '>=', $end)
            ->exists();

        abort_unless($isWorkingSlot, 422, 'This time slot is not within your working schedule, so it cannot be blocked.');

        $this->ensureNoDuplicateBlock($instructorData->id, $validated['day_of_week'], $validated['time_id']);

        $block = $instructorData->scheduleBlocks()->create([
            'day_of_week' => $validated['day_of_week'],
            'time_id' => $validated['time_id'],
            'reason' => $validated['reason'] ?? null,
            'status' => InstructorScheduleBlock::STATUS_ACTIVE,
        ]);

        $block->load('time:id,time_name');

        return response()->json($this->presentBlock($block), 201);
    }

    public function destroy(InstructorScheduleBlock $block): JsonResponse
    {
        $block->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Blocks every available slot selected from one visible calendar row in a
     * single transaction. A row may contain fewer slots on shorter work days.
     */
    public function storeRow(Request $request): JsonResponse
    {
        $instructorData = $request->user()->instructorData()->first();

        abort_unless($instructorData, 404, 'No instructor profile found for this account.');

        $validated = $request->validate([
            'slots' => ['required', 'array', 'min:1', 'max:7'],
            'slots.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'slots.*.time_id' => ['required', 'integer', 'exists:times,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $slots = collect($validated['slots'])
            ->unique(fn (array $slot): string => "{$slot['day_of_week']}:{$slot['time_id']}")
            ->values();

        abort_if($slots->count() !== count($validated['slots']), 422, 'Each slot can only be included once.');

        $timeIds = $slots->pluck('time_id')->all();
        $times = Time::query()->whereIn('id', $timeIds)->get()->keyBy('id');

        foreach ($slots as $slot) {
            $time = $times->get($slot['time_id']);
            $range = StudyClass::parseTimeRange($time->time_name);
            $start = $range['start'] ?? null;
            $end = $range['end'] ?? null;

            $isWorkingSlot = $start !== null && $end !== null && $instructorData->availabilities()
                ->where('is_active', true)
                ->where('day_of_week', $slot['day_of_week'])
                ->where('start_time', '<=', $start)
                ->where('end_time', '>=', $end)
                ->exists();

            abort_unless($isWorkingSlot, 422, 'One or more selected slots are not within your working schedule.');
            $this->ensureNoDuplicateBlock($instructorData->id, $slot['day_of_week'], $slot['time_id']);
        }

        $blocks = DB::transaction(function () use ($instructorData, $slots, $validated): array {
            return $slots->map(fn (array $slot): InstructorScheduleBlock => $instructorData->scheduleBlocks()->create([
                'day_of_week' => $slot['day_of_week'],
                'time_id' => $slot['time_id'],
                'reason' => $validated['reason'] ?? null,
                'status' => InstructorScheduleBlock::STATUS_ACTIVE,
            ]))->all();
        });

        foreach ($blocks as $block) {
            $block->load('time:id,time_name');
        }

        return response()->json([
            'blocks' => array_map($this->presentBlock(...), $blocks),
        ], 201);
    }

    /** Removes all selected manual blocks from one visible calendar row. */
    public function destroyRow(Request $request): JsonResponse
    {
        $instructorData = $request->user()->instructorData()->first();

        abort_unless($instructorData, 404, 'No instructor profile found for this account.');

        $validated = $request->validate([
            'block_ids' => ['required', 'array', 'min:1', 'max:7'],
            'block_ids.*' => ['required', 'integer', 'distinct', 'exists:instructor_schedule_blocks,id'],
        ]);

        $deletedIds = DB::transaction(function () use ($instructorData, $validated): array {
            $blocks = InstructorScheduleBlock::query()
                ->where('instructor_id', $instructorData->id)
                ->where('status', InstructorScheduleBlock::STATUS_ACTIVE)
                ->whereIn('id', $validated['block_ids'])
                ->lockForUpdate()
                ->get();

            abort_if($blocks->count() !== count($validated['block_ids']), 422, 'One or more selected blocks are no longer active.');

            $ids = $blocks->pluck('id')->all();
            InstructorScheduleBlock::query()->whereIn('id', $ids)->delete();

            return $ids;
        });

        return response()->json(['deleted_ids' => $deletedIds]);
    }

    private function ensureNoDuplicateBlock(int $instructorId, int $dayOfWeek, int $timeId): void
    {
        $exists = InstructorScheduleBlock::query()
            ->where('instructor_id', $instructorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('time_id', $timeId)
            ->where('status', InstructorScheduleBlock::STATUS_ACTIVE)
            ->exists();

        abort_if($exists, 422, 'You already have an active block for that day and time.');
    }

    private function presentBlock(InstructorScheduleBlock $block): array
    {
        return [
            'id' => $block->id,
            'day_of_week' => $block->day_of_week,
            'time_id' => $block->time_id,
            'time_name' => $block->time?->time_name,
            'reason' => $block->reason,
        ];
    }
}
