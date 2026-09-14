<?php

namespace App\Modules\Instructor\Services;

use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Models\StudyClass;
use App\Models\Time;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;

/**
 * Admin / super_admin edits to a single instructor's weekly availability, made
 * from the Instructor Busy Time grid. Everything here is idempotent-safe and
 * validates the same way the instructor's own self-service path does
 * (InstructorScheduleBlockController), just for an arbitrary instructor.
 */
class InstructorAvailabilityAdminService
{
    public function __construct(private InstructorAssignmentAvailability $instructorAvailability) {}

    /**
     * Manually block a working slot for an instructor. `created_by` records the
     * admin so the grid can show "blocked by admin" vs the instructor's own block.
     */
    public function blockSlot(InstructorData $instructor, int $dayOfWeek, int $timeId, ?string $reason, int $actorId): InstructorScheduleBlock
    {
        [$start, $end] = $this->range($timeId);

        abort_unless(
            $this->slotInWorkingWindow($instructor, $dayOfWeek, $start, $end),
            422,
            'That time slot is outside the instructor\'s working schedule. Open it first, then block it.',
        );

        abort_if(
            $this->slotOverlapsOpenClass($instructor, $dayOfWeek, $start, $end),
            422,
            'The instructor is teaching a class in that slot - cancel or move the class instead.',
        );

        abort_if(
            InstructorScheduleBlock::query()
                ->where('instructor_id', $instructor->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('time_id', $timeId)
                ->where('status', InstructorScheduleBlock::STATUS_ACTIVE)
                ->exists(),
            422,
            'That slot is already blocked.',
        );

        $block = $instructor->scheduleBlocks()->create([
            'day_of_week' => $dayOfWeek,
            'time_id' => $timeId,
            'reason' => $reason,
            'status' => InstructorScheduleBlock::STATUS_ACTIVE,
            'created_by' => $actorId,
        ]);

        return $block->load('time:id,time_name');
    }

    public function unblockSlot(InstructorScheduleBlock $block): void
    {
        $block->delete();
    }

    /**
     * Open a slot the instructor is not normally available for (outside their
     * work schedule). Persisted as an instructor_availabilities row with
     * source = 'admin' so schedule regeneration leaves it alone.
     */
    public function openSlot(InstructorData $instructor, int $dayOfWeek, int $timeId): InstructorAvailability
    {
        [$start, $end] = $this->range($timeId);

        abort_if(
            $this->slotInWorkingWindow($instructor, $dayOfWeek, $start, $end),
            422,
            'That slot is already open for this instructor.',
        );

        abort_if(
            $this->slotOverlapsOpenClass($instructor, $dayOfWeek, $start, $end),
            422,
            'The instructor already has a class in that slot.',
        );

        return $instructor->availabilities()->create([
            'day_of_week' => $dayOfWeek,
            'employment_type' => $instructor->employment_type ?: 'full_time',
            'shift_group' => 'admin_override',
            'period' => $this->periodFor($start),
            'start_time' => $start,
            'end_time' => $end,
            'is_active' => true,
            'source' => InstructorAvailability::SOURCE_ADMIN,
        ]);
    }

    public function closeSlot(InstructorAvailability $availability): void
    {
        abort_unless(
            $availability->source === InstructorAvailability::SOURCE_ADMIN,
            422,
            'This slot comes from the instructor\'s work schedule - block it instead of closing it.',
        );

        $availability->delete();
    }

    /**
     * @return array{0: string, 1: string} parsed [start, end] as "HH:MM"
     */
    private function range(int $timeId): array
    {
        $time = Time::query()->findOrFail($timeId);
        $range = StudyClass::parseTimeRange($time->time_name);

        abort_if(
            $range['start'] === null || $range['end'] === null,
            422,
            'That time slot has an unrecognised time format.',
        );

        return [$range['start'], $range['end']];
    }

    private function slotInWorkingWindow(InstructorData $instructor, int $dayOfWeek, string $start, string $end): bool
    {
        return $instructor->availabilities()
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<=', $start)
            ->where('end_time', '>=', $end)
            ->exists();
    }

    private function slotOverlapsOpenClass(InstructorData $instructor, int $dayOfWeek, string $start, string $end): bool
    {
        return $this->instructorAvailability->occupiedSlots($instructor->user_id)
            ->where('day_of_week', $dayOfWeek)
            ->contains(fn (array $o): bool => $start < $o['end'] && $end > $o['start']);
    }

    private function periodFor(string $start): string
    {
        return match (true) {
            $start >= '17:00' => 'evening',
            $start >= '12:00' => 'afternoon',
            default => 'morning',
        };
    }
}
