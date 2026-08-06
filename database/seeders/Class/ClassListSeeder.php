<?php

namespace Database\Seeders\Class;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reuses rooms created by Database\Seeders\Building\BuildingSeeder instead of
        // fabricating placeholder buildings/floors/rooms, so this data matches what
        // admins actually see in Building Management.
        $rooms = DB::table('rooms')
            ->join('floors', 'floors.id', '=', 'rooms.floor_id')
            ->orderBy('rooms.id')
            ->limit(5)
            ->get(['rooms.id as room_id', 'rooms.floor_id', 'floors.building_id'])
            ->values();

        if ($rooms->count() < 5) {
            throw new \RuntimeException(
                'ClassListSeeder needs at least 5 seeded rooms. Run Database\\Seeders\\Building\\BuildingSeeder first.'
            );
        }

        $items = [
            [
                'teacher_id'    => 1,
                'course_id'     => 1,
                'lesson_id'     => 1,
                'term_id'       => 1,
                'time_id'       => 1,
                'room'          => $rooms[0],
                'class_type_id' => 1,
                'student_count' => 22,
                'status'        => 'progress',
            ],
            [
                'teacher_id'    => 2,
                'course_id'     => 2,
                'lesson_id'     => 2,
                'term_id'       => 2,
                'time_id'       => 2,
                'room'          => $rooms[1],
                'class_type_id' => 2,
                'student_count' => 18,
                'status'        => 'completed',
            ],
            [
                'teacher_id'    => 3,
                'course_id'     => 3,
                'lesson_id'     => 3,
                'term_id'       => 1,
                'time_id'       => 3,
                'room'          => $rooms[2],
                'class_type_id' => 3,
                'student_count' => 30,
                'status'        => 'progress',
            ],
            [
                'teacher_id'    => 4,
                'course_id'     => 4,
                'lesson_id'     => 4,
                'term_id'       => 2,
                'time_id'       => 4,
                'room'          => $rooms[3],
                'class_type_id' => 4,
                'student_count' => 16,
                'status'        => 'cancelled',
            ],
            [
                'teacher_id'    => 1,
                'course_id'     => 2,
                'lesson_id'     => 3,
                'term_id'       => 3,
                'time_id'       => 5,
                'room'          => $rooms[4],
                'class_type_id' => 1,
                'student_count' => 25,
                'status'        => 'progress',
            ],
        ];

        DB::table('class_list')->truncate();

        foreach ($items as &$item) {
            $room = $item['room'];
            unset($item['room']);

            $item['building_id'] = $room->building_id;
            $item['floor_id'] = $room->floor_id;
            $item['room_id'] = $room->room_id;
            $item['time_id'] = $this->ensureTime((int) $item['time_id']);
            $item['created_at'] = now();
            $item['updated_at'] = now();
        }
        unset($item);

        foreach ($items as $item) {
            DB::table('class_list')->insert($item);
        }
    }

    private function ensureTime(int $id): int
    {
        // insertOrIgnore: TimeSeeder already seeds real time slot names before this
        // runs, so this must not overwrite them with a generic placeholder.
        DB::table('times')->insertOrIgnore([
            'id' => $id,
            'time_name' => "Time {$id}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
