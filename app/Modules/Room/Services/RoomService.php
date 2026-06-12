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
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(StoreRoomData $data): Room
    {
        return Room::create($data->roomAttributes());
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