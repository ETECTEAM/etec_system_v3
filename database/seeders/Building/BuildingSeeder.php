<?php

namespace Database\Seeders\Building;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use Illuminate\Database\Seeder;

class BuildingSeeder extends Seeder
{
    public function run(): void
    {
        $buildings = [
            [
                'name' => 'Building A',
                'code' => 'A',
                'floors' => [
                    [
                        'name' => 'Floor-1',
                        'level' => 1,
                        'rooms' => ['ETEC A101', 'ETEC A102', 'ETEC A103(Hall)', 'ETEC A104'],
                    ],
                    [
                        'name' => 'Floor-2',
                        'level' => 2,
                        'rooms' => ['ETEC A203', 'ETEC A202'],
                    ],
                    [
                        'name' => 'Mezzanine',
                        'level' => 0,
                        'rooms' => ['ETEC A01'],
                    ],
                ],
            ],
            [
                'name' => 'Building B',
                'code' => 'B',
                'floors' => [
                    [
                        'name' => 'Floor-1',
                        'level' => 1,
                        'rooms' => ['ETEC B101', 'ETEC B102', 'ETEC B103', 'ETEC B104', 'ETEC B105', 'ETEC B106'],
                    ],
                    [
                        'name' => 'Floor-2',
                        'level' => 2,
                        'rooms' => ['ETEC B201', 'ETEC B202', 'ETEC B203', 'ETEC B204', 'ETEC B205', 'ETEC B206'],
                    ],
                    [
                        'name' => 'Floor-3',
                        'level' => 3,
                        'rooms' => ['ETEC B301', 'ETEC B302', 'ETEC B303', 'ETEC Design', 'ETEC Office'],
                    ],
                    [
                        'name' => 'Ground-Floor',
                        'level' => 0,
                        'rooms' => ['ETEC B01', 'ETEC B02'],
                    ],
                ],
            ],
        ];

        foreach ($buildings as $buildingData) {
            $building = Building::updateOrCreate(
                ['name' => $buildingData['name']],
                ['code' => $buildingData['code']]
            );

            foreach ($buildingData['floors'] as $floorData) {
                $floor = Floor::updateOrCreate(
                    [
                        'building_id' => $building->id,
                        'name' => $floorData['name'],
                    ],
                    ['level' => $floorData['level']]
                );

                foreach ($floorData['rooms'] as $roomNumber) {
                    Room::updateOrCreate(
                        [
                            'floor_id' => $floor->id,
                            'room_number' => $roomNumber,
                        ],
                        ['status' => 'available']
                    );
                }
            }
        }
    }
}
