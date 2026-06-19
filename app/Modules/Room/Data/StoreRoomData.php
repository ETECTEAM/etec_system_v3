<?php

namespace App\Modules\Room\Data;

readonly class StoreRoomData
{
    public function __construct(
        public ?int $floor_id,
        public string $room_number,
        public ?int $capacity,
        public string $status,
    ) {}

    public function roomAttributes(): array
    {
        return [
            'floor_id' => $this->floor_id,
            'room_number' => $this->room_number,
            'capacity' => $this->capacity,
            'status' => $this->status,
        ];
    }
}