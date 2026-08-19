<?php

namespace App\Modules\Instructor\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InstructorScheduleBlock;
use App\Models\StudyClass;
use App\Models\Time;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstructorScheduleBlockController extends Controller
{
    private const DAY_LABELS = [
        1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
        5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
    ];

    private const SHORT_DAY_LABELS = [
        1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu',
        5 => 'Fri', 6 => 'Sat', 7 => 'Sun',
    ];

    public function index(Request $request): Response
    {
        $instructorData = $request->user()->instructorData()
            ->with(['workSchedule'])
            ->first();

        abort_unless($instructorData, 404, 'No instructor profile found for this account.');

        $availabilities = $instructorData->availabilities()
            ->where('is_active', true)
            ->get(['day_of_week', 'start_time', 'end_time']);

        $workingDays = $availabilities->pluck('day_of_week')->unique()->sort()->values()->all();
        $workingDayNames = array_map(fn (int $d) => self::SHORT_DAY_LABELS[$d] ?? (string) $d, $workingDays);

        $workingHours = $availabilities->groupBy('day_of_week')->map(function ($daySlots) {
            return $daySlots->map(fn ($s) => substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5))->implode(' / ');
        })->values()->implode(' · ');

        return Inertia::render('backend/instructors/ScheduleBlocks', [
            'instructorName' => $instructorData->full_name,
            'workSchedule' => $instructorData->workSchedule
                ? ['name' => $instructorData->workSchedule->name]
                : null,
            'workingDaysLabel' => $workingDayNames ? implode('–', [$workingDayNames[0], end($workingDayNames)]) : 'None',
            'workingHours' => $workingHours,
        ]);
    }

    /**
     * Returns a 7-day calendar structure. Every day of the week is always
     * present so the frontend can render a fixed 7-column calendar. Days
     * where the instructor has no WorkScheduleTime entries are marked as
     * non-working. For working days, only Time records that are linked to
     * the instructor's WorkSchedule for that day are shown.
     */
    public function data(Request $request): JsonResponse
    {
        $instructorData = $request->user()->instructorData()->first();

        abort_unless($instructorData, 404, 'No instructor profile found for this account.');

        $availabilities = $instructorData->availabilities()->where('is_active', true)->get(['day_of_week', 'start_time', 'end_time']);
        $blocks = $instructorData->scheduleBlocks()->where('status', InstructorScheduleBlock::STATUS_ACTIVE)->get(['id', 'day_of_week', 'time_id', 'reason']);

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

        // Deduplicate by time_name: if the times table has duplicate entries
        // with the same label but different IDs, we collapse them into one.
        $timeNameToId = $workScheduleTimes
            ->filter(fn ($wst) => $wst->time)
            ->mapWithKeys(fn ($wst) => [$wst->time->time_name => $wst->time_id])
            ->unique()
            ->values()
            ->flip(); // time_name => time_id

        // Build a lookup of Time models keyed by ID.
        $timeModels = $timeNameToId->isNotEmpty()
            ? Time::query()->whereIn('id', $timeNameToId->values()->toArray())->get(['id', 'time_name'])->keyBy('id')
            : collect();

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

        $schedule = collect(range(1, 7))->map(function (int $day) use ($dayWindows, $workingDays, $blocks, $dayTimeIds, $timeModels): array {
            $isWorking = in_array($day, $workingDays);

            // Only Time records assigned to THIS day via WorkScheduleTime.
            $dayTimeEntries = $dayTimeIds->get($day, collect());

            $slots = $dayTimeEntries->map(function (int $timeId) use ($day, $isWorking, $dayWindows, $blocks, $timeModels): array {
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

                $block = $blocks->first(fn (InstructorScheduleBlock $b): bool => $b->day_of_week === $day && $b->time_id === $timeId);

                return [
                    'time_id' => $timeId,
                    'time_name' => $timeName,
                    'status' => $block !== null ? 'blocked' : ($isAvailable ? 'available' : 'not_working'),
                    'block_id' => $block?->id,
                    'reason' => $block?->reason,
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
