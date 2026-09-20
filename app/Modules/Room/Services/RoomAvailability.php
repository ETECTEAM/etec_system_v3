<?php

namespace App\Modules\Room\Services;

use App\Models\ClassType;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use Illuminate\Support\Collection;

/**
 * Whether a room is free at a term/time slot, worked out from the open classes that hold it.
 * Room status is not used for this: a room is busy on certain days and hours, not "occupied" overall.
 * Only rooms with status "available" can be booked at all (maintenance/closed never are).
 */
class RoomAvailability
{
    private const OPEN_CLASS_STATUSES = ['upcoming', 'active', 'pre_end'];

    /** @var Collection<int, array{id: int, title: string, room_id: int, days: list<string>, start: string, end: string, teacher: ?string}>|null */
    private ?Collection $bookings = null;

    /** @var array<string, array{days: list<string>, start: string, end: string}|null> */
    private array $slots = [];

    /**
     * Returns a validation message when the room is already taken (or unusable) for this
     * term/time, or null when it can be booked.
     */
    public function unavailableReason(int $roomId, int $termId, int $timeId, ?int $exceptClassId = null): ?string
    {
        $status = Room::query()->whereKey($roomId)->value('status');

        if ($status !== 'available') {
            return 'The selected room is not available for classes.';
        }

        $booking = $this->bookingsFor($termId, $timeId, $exceptClassId)->firstWhere('room_id', $roomId);

        if ($booking !== null) {
            return "The selected room is already used by {$booking['title']} at this time.";
        }

        return null;
    }

    /**
     * Bookings (one per class) that overlap the given term/time slot: same weekday and an
     * overlapping time range, not just the same time_id (9:00-10:30 and 9:00-11:00 overlap).
     *
     * @return Collection<int, array{id: int, title: string, room_id: int, days: list<string>, start: string, end: string, teacher: ?string}>
     */
    public function bookingsFor(int $termId, int $timeId, ?int $exceptClassId = null): Collection
    {
        $slot = $this->slot($termId, $timeId);

        if ($slot === null) {
            return collect();
        }

        return $this->bookings()
            ->reject(fn (array $booking): bool => $booking['id'] === $exceptClassId)
            ->filter(fn (array $booking): bool => array_intersect($slot['days'], $booking['days']) !== []
                && $slot['start'] < $booking['end']
                && $slot['end'] > $booking['start'])
            ->values();
    }

    /**
     * Room ids already taken per "termId:timeId", for every slot of every non-online schedule,
     * so a class form can hide taken rooms as soon as a term and time are chosen.
     *
     * @return array<string, list<int>>
     */
    public function busyRoomIdsBySlot(): array
    {
        $busy = [];

        foreach ($this->nonOnlineSchedules() as $schedule) {
            foreach ($schedule->times as $time) {
                $roomIds = $this->bookingsFor($schedule->term_id, $time->id)->pluck('room_id')->unique()->values()->all();

                if ($roomIds !== []) {
                    $busy["{$schedule->term_id}:{$time->id}"] = $roomIds;
                }
            }
        }

        return $busy;
    }

    /**
     * Free/busy rooms for every term and time of one class type's schedule.
     *
     * @return array{terms: list<array<string, mixed>>, unassigned: list<array<string, mixed>>, roomCount: int}
     */
    public function overview(int $classTypeId): array
    {
        $rooms = Room::query()
            ->with('floor.building')
            ->where('status', 'available')
            ->get()
            ->sortBy(fn (Room $room): string => sprintf('%s|%s|%s', $room->floor?->building?->name ?? '', $room->floor?->level ?? '', $room->room_number), SORT_NATURAL)
            ->values();

        $terms = Schedule::query()
            ->with(['term', 'times'])
            ->where('class_type_id', $classTypeId)
            ->get()
            ->sortBy(fn (Schedule $schedule): string => $schedule->term?->term_name ?? '')
            ->map(fn (Schedule $schedule): array => [
                'term_id' => $schedule->term_id,
                'term_name' => $schedule->term?->term_name ?? '-',
                'times' => $schedule->times
                    ->sortBy(fn (Time $time): string => StudyClass::parseTimeRange($time->time_name)['start'] ?? '99:99')
                    ->values()
                    ->map(fn (Time $time): array => $this->slotRow($schedule->term_id, $time, $rooms))
                    ->all(),
            ])
            ->values()
            ->all();

        return [
            'terms' => $terms,
            'unassigned' => $this->classesWithoutRoom(),
            'roomCount' => $rooms->count(),
        ];
    }

    /**
     * @param  Collection<int, Room>  $rooms
     * @return array<string, mixed>
     */
    private function slotRow(int $termId, Time $time, Collection $rooms): array
    {
        $bookings = $this->bookingsFor($termId, $time->id)->keyBy('room_id');

        $rows = $rooms->map(function (Room $room) use ($bookings): array {
            $booking = $bookings->get($room->id);

            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'capacity' => $room->capacity,
                'location' => collect([$room->floor?->building?->name, $room->floor?->name])->filter()->implode(' · '),
                'busy' => $booking !== null,
                'class_title' => $booking['title'] ?? null,
                'teacher' => $booking['teacher'] ?? null,
            ];
        });

        return [
            'time_id' => $time->id,
            'time_name' => $time->time_name,
            'free' => $rows->where('busy', false)->count(),
            'busy' => $rows->where('busy', true)->count(),
            'rooms' => $rows->all(),
        ];
    }

    /**
     * Open in-person classes that were created without a room yet (admins can leave it for the instructor).
     *
     * @return list<array<string, mixed>>
     */
    private function classesWithoutRoom(): array
    {
        return StudyClass::query()
            ->whereIn('status', self::OPEN_CLASS_STATUSES)
            ->whereNull('room_id')
            ->with(['term:id,term_name', 'time:id,time_name', 'teacher:id,name'])
            ->get(['id', 'title', 'term_id', 'time_id', 'teacher_id', 'class_type_id'])
            ->reject(fn (StudyClass $class): bool => $this->isOnlineType($class->class_type_id))
            ->map(fn (StudyClass $class): array => [
                'id' => $class->id,
                'title' => $class->title,
                'term_name' => $class->term?->term_name,
                'time_name' => $class->time?->time_name,
                'teacher' => $class->teacher?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{id: int, title: string, room_id: int, days: list<string>, start: string, end: string, teacher: ?string}>
     */
    private function bookings(): Collection
    {
        return $this->bookings ??= StudyClass::query()
            ->whereIn('status', self::OPEN_CLASS_STATUSES)
            ->whereNotNull('room_id')
            ->with(['term:id,term_name', 'time:id,time_name', 'teacher:id,name'])
            ->get(['id', 'title', 'room_id', 'term_id', 'time_id', 'teacher_id'])
            ->map(function (StudyClass $class): ?array {
                $range = StudyClass::parseTimeRange($class->time?->time_name);
                $days = StudyClass::parseTermDays($class->term?->term_name);

                if ($range['start'] === null || $range['end'] === null || $days === []) {
                    return null;
                }

                return [
                    'id' => $class->id,
                    'title' => $class->title,
                    'room_id' => (int) $class->room_id,
                    'days' => $days,
                    'start' => $range['start'],
                    'end' => $range['end'],
                    'teacher' => $class->teacher?->name,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return array{days: list<string>, start: string, end: string}|null
     */
    private function slot(int $termId, int $timeId): ?array
    {
        return array_key_exists("$termId:$timeId", $this->slots)
            ? $this->slots["$termId:$timeId"]
            : $this->slots["$termId:$timeId"] = $this->resolveSlot($termId, $timeId);
    }

    /**
     * @return array{days: list<string>, start: string, end: string}|null
     */
    private function resolveSlot(int $termId, int $timeId): ?array
    {
        $termName = Term::query()->whereKey($termId)->value('term_name');
        $range = StudyClass::parseTimeRange(Time::query()->whereKey($timeId)->value('time_name'));
        $days = StudyClass::parseTermDays($termName);

        if ($days === [] || $range['start'] === null || $range['end'] === null) {
            return null;
        }

        return ['days' => $days, 'start' => $range['start'], 'end' => $range['end']];
    }

    /**
     * @return Collection<int, Schedule>
     */
    private function nonOnlineSchedules(): Collection
    {
        return Schedule::query()
            ->with(['times', 'classType'])
            ->get()
            ->reject(fn (Schedule $schedule): bool => $schedule->classType?->isOnline() ?? false);
    }

    private function isOnlineType(?int $classTypeId): bool
    {
        return $classTypeId !== null && (ClassType::query()->find($classTypeId)?->isOnline() ?? false);
    }
}
