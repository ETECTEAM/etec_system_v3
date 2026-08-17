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

    public function index(Request $request): Response
    {
        $instructorData = $request->user()->instructorData()->first(['id', 'full_name']);

        abort_unless($instructorData, 404, 'No instructor profile found for this account.');

        return Inertia::render('backend/instructors/ScheduleBlocks', [
            'instructorName' => $instructorData->full_name,
        ]);
    }

    // Everything one instructor's schedule-block page needs in one call, fully
    // resolved server-side. Working windows come from InstructorAvailability -
    // the materialized copy of whichever ShiftTemplate/shift_group the
    // instructor is actually on. Only days with at least one working window
    // are returned; every real Time record is classified per day as
    // available / not_working / blocked so the frontend never has to
    // re-derive time-range overlaps itself.
    public function data(Request $request): JsonResponse
    {
        $instructorData = $request->user()->instructorData()->first();

        abort_unless($instructorData, 404, 'No instructor profile found for this account.');

        $availabilities = $instructorData->availabilities()->where('is_active', true)->get(['day_of_week', 'start_time', 'end_time']);
        $times = Time::query()->orderBy('id')->get(['id', 'time_name']);
        $blocks = $instructorData->scheduleBlocks()->where('status', InstructorScheduleBlock::STATUS_ACTIVE)->get(['id', 'day_of_week', 'time_id', 'reason']);

        $schedule = $availabilities->pluck('day_of_week')->unique()->sort()->values()
            ->map(function (int $day) use ($availabilities, $times, $blocks): array {
                $dayAvailabilities = $availabilities->where('day_of_week', $day);

                return [
                    'day_of_week' => $day,
                    'day_label' => self::DAY_LABELS[$day] ?? (string) $day,
                    'slots' => $times->map(function (Time $time) use ($day, $dayAvailabilities, $blocks): array {
                        $range = StudyClass::parseTimeRange($time->time_name);
                        $start = $range['start'] ?? null;
                        $end = $range['end'] ?? null;

                        $isWorking = $start !== null && $end !== null && $dayAvailabilities->contains(
                            fn ($availability): bool => substr($availability->start_time, 0, 5) <= $start && substr($availability->end_time, 0, 5) >= $end
                        );

                        $block = $blocks->first(fn (InstructorScheduleBlock $block): bool => $block->day_of_week === $day && $block->time_id === $time->id);

                        return [
                            'time_id' => $time->id,
                            'time_name' => $time->time_name,
                            'status' => $block !== null ? 'blocked' : ($isWorking ? 'available' : 'not_working'),
                            'block_id' => $block?->id,
                            'reason' => $block?->reason,
                        ];
                    })->values(),
                ];
            })->values();

        return response()->json(['schedule' => $schedule]);
    }

    // Blocking only makes sense on a slot the instructor actually works -
    // reject anything outside their InstructorAvailability windows for that
    // day, and reject a second active block on the same (day, time) pair.
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
