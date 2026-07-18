<?php

namespace App\Modules\building\Services;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use App\Modules\building\Data\BuildingData;
use App\Modules\Floor\Data\FloorData;
use App\Modules\Room\Data\StoreRoomData;
use App\Modules\Room\Data\UpdateRoomData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuildingService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function hierarchy(): Collection
    {
        return Building::query()
            ->with([
                'floors' => fn ($query) => $query
                    ->orderByRaw('level is null')
                    ->orderBy('level')
                    ->orderBy('name')
                    ->with(['rooms' => fn ($roomQuery) => $roomQuery->orderBy('room_number')]),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Building $building): array => $this->presentBuilding($building));
    }

    public function create(BuildingData $data): Building
    {
        return Building::query()->create($data->attributes());
    }

    public function update(Building $building, BuildingData $data): Building
    {
        $building->update($data->attributes());

        return $building->fresh(['floors.rooms']);
    }

    public function delete(Building $building): void
    {
        DB::transaction(function () use ($building): void {
            $building->loadMissing('floors.rooms');

            foreach ($building->floors as $floor) {
                $floor->rooms()->delete();
            }

            $building->floors()->delete();
            $building->delete();
        });
    }

    public function createFloor(Building $building, FloorData $data): Floor
    {
        return $building->floors()->create($data->attributes());
    }

    /**
     * @return Collection<int, Floor>
     */
    public function createFloorSequence(Building $building, string $startName, int $totalFloors, ?int $startLevel): Collection
    {
        $startName = trim($startName);
        $floorNames = $this->buildFloorNames($startName, $totalFloors);

        $existingNames = $building->floors()
            ->whereIn('name', $floorNames)
            ->pluck('name')
            ->all();

        if ($existingNames !== []) {
            throw ValidationException::withMessages([
                'start_name' => ['Some generated floor names already exist: '.implode(', ', $existingNames)],
            ]);
        }

        if ($startLevel !== null) {
            $levels = collect(range(0, $totalFloors - 1))
                ->map(fn (int $offset): int => $startLevel + $offset);

            $existingLevels = $building->floors()
                ->whereIn('level', $levels)
                ->pluck('level')
                ->all();

            if ($existingLevels !== []) {
                throw ValidationException::withMessages([
                    'start_level' => ['Some generated levels already exist in this building: '.implode(', ', $existingLevels)],
                ]);
            }
        }

        return $floorNames->values()->map(function (string $name, int $index) use ($building, $startLevel): Floor {
            return $building->floors()->create([
                'building_id' => $building->id,
                'name' => $name,
                'code' => null,
                'level' => $startLevel !== null ? $startLevel + $index : null,
            ]);
        });
    }

    /**
     * @return Collection<int, string>
     */
    private function buildFloorNames(string $startName, int $totalFloors): Collection
    {
        if (preg_match('/^[A-Za-z]+$/', $startName) === 1) {
            $baseValue = $this->lettersToNumber(strtoupper($startName));

            return collect(range(0, $totalFloors - 1))
                ->map(fn (int $offset): string => $this->numberToLetters($baseValue + $offset));
        }

        preg_match('/^(.*?)(\d+)$/', $startName, $matches);

        $prefix = $matches[1] ?? '';
        $startingNumber = (int) ($matches[2] ?? 0);
        $width = strlen($matches[2] ?? '');

        return collect(range(0, $totalFloors - 1))
            ->map(fn (int $offset): string => $prefix.str_pad((string) ($startingNumber + $offset), $width, '0', STR_PAD_LEFT));
    }

    private function lettersToNumber(string $letters): int
    {
        $value = 0;

        foreach (str_split($letters) as $character) {
            $value = ($value * 26) + (ord($character) - 64);
        }

        return $value;
    }

    private function numberToLetters(int $number): string
    {
        $letters = '';

        while ($number > 0) {
            $number--;
            $letters = chr(($number % 26) + 65).$letters;
            $number = intdiv($number, 26);
        }

        return $letters;
    }

    public function updateFloor(Building $building, Floor $floor, FloorData $data): Floor
    {
        abort_unless((int) $floor->building_id === (int) $building->id, 404);

        $floor->update($data->attributes());

        return $floor->fresh(['rooms']);
    }

    public function deleteFloor(Building $building, Floor $floor): void
    {
        abort_unless((int) $floor->building_id === (int) $building->id, 404);

        DB::transaction(function () use ($floor): void {
            $floor->rooms()->delete();
            $floor->delete();
        });
    }

    public function createRoom(Building $building, Floor $floor, StoreRoomData $data): Room
    {
        abort_unless((int) $floor->building_id === (int) $building->id, 404);

        return $floor->rooms()->create($data->roomAttributes());
    }

    public function updateRoom(Building $building, Floor $floor, Room $room, UpdateRoomData $data): Room
    {
        abort_unless((int) $floor->building_id === (int) $building->id, 404);
        abort_unless((int) $room->floor_id === (int) $floor->id, 404);

        $room->update($data->roomAttributes());

        return $room->fresh();
    }

    public function deleteRoom(Building $building, Floor $floor, Room $room): void
    {
        abort_unless((int) $floor->building_id === (int) $building->id, 404);
        abort_unless((int) $room->floor_id === (int) $floor->id, 404);

        $room->delete();
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        return [
            'buildings' => Building::query()->count(),
            'floors' => Floor::query()->count(),
            'rooms' => Room::query()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentBuilding(Building $building): array
    {
        $floors = $building->floors->map(fn (Floor $floor): array => [
            'id' => $floor->id,
            'building_id' => $floor->building_id,
            'name' => $floor->name,
            'level' => $floor->level,
            'rooms_count' => $floor->rooms->count(),
            'rooms' => $floor->rooms->map(fn (Room $room): array => [
                'id' => $room->id,
                'floor_id' => $room->floor_id,
                'room_number' => $room->room_number,
                'capacity' => $room->capacity,
                'status' => $room->status,
            ])->values()->all(),
        ])->values();

        return [
            'id' => $building->id,
            'name' => $building->name,
            'code' => $building->code,
            'description' => $building->description,
            'floors_count' => $floors->count(),
            'rooms_count' => $floors->sum('rooms_count'),
            'floors' => $floors->all(),
        ];
    }
}
