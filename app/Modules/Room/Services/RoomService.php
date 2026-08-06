<?php

namespace App\Modules\Room\Services;

use App\Models\Room;
use App\Modules\Room\Data\StoreRoomData;
use App\Modules\Room\Data\UpdateRoomData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoomService
{
    public function paginateRooms(array $filters = [], int $perPage = 5): LengthAwarePaginator
    {
        return Room::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('room_number', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(StoreRoomData $data): Room
    {
        return Room::create($data->roomAttributes());
    }

    /**
     * @return \Illuminate\Support\Collection<int, Room>
     */
    public function createSequence(
        ?int $floorId,
        string $startRoomNumber,
        int $totalRooms,
        ?int $capacity,
        string $status,
    ): \Illuminate\Support\Collection {
        preg_match('/^(.*?)(\d+)$/', $startRoomNumber, $matches);

        $prefix = $matches[1] ?? '';
        $startingNumber = (int) ($matches[2] ?? 0);
        $width = strlen($matches[2] ?? '');

        $roomNumbers = collect(range(0, $totalRooms - 1))
            ->map(fn (int $offset): string => $prefix.str_pad((string) ($startingNumber + $offset), $width, '0', STR_PAD_LEFT));

        $duplicatesInDatabase = Room::query()
            ->where('floor_id', $floorId)
            ->whereIn('room_number', $roomNumbers)
            ->pluck('room_number')
            ->all();

        if ($duplicatesInDatabase !== []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'start_room_number' => ['Some generated room numbers already exist: '.implode(', ', $duplicatesInDatabase)],
            ]);
        }

        return $roomNumbers->map(function (string $roomNumber) use ($floorId, $capacity, $status): Room {
            \Illuminate\Support\Facades\Validator::make([
                'floor_id' => $floorId,
                'room_number' => $roomNumber,
                'capacity' => $capacity,
                'status' => $status,
            ], [
                'floor_id' => ['nullable', 'integer', 'exists:floors,id'],
                'room_number' => ['required', 'string', 'max:255'],
                'capacity' => ['nullable', 'integer', 'min:1'],
                'status' => ['required', 'string'],
            ])->validate();

            return Room::query()->create([
                'floor_id' => $floorId,
                'room_number' => $roomNumber,
                'capacity' => $capacity,
                'status' => $status,
            ]);
        });
    }


    public function update(Room $room, UpdateRoomData $data): Room
    {
        $room->update($data->roomAttributes());

        return $room;
    }

    public function delete(Room $room): void
    {
        $room->delete();
    }

    public function presentRoom(Room $room): array
    {
        return [
            'id' => $room->id,
            'floor_id' => $room->floor_id,
            'room_number' => $room->room_number,
            'capacity' => $room->capacity,
            'status' => $room->status,
            'created_at' => $room->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
