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
        $items = [
            [
                'teacher_id'    => 1,
                'course_id'     => 1,
                'lesson_id'     => 1,
                'term_id'       => 1,
                'time_id'       => 1,
                'building_id'   => 1,
                'floor_id'      => 1,
                'room_id'       => 1,
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
                'building_id'   => 1,
                'floor_id'      => 2,
                'room_id'       => 2,
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
                'building_id'   => 2,
                'floor_id'      => 3,
                'room_id'       => 3,
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
                'building_id'   => 2,
                'floor_id'      => 1,
                'room_id'       => 4,
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
                'building_id'   => 3,
                'floor_id'      => 2,
                'room_id'       => 5,
                'class_type_id' => 1,
                'student_count' => 25,
                'status'        => 'progress',
            ],
        ];

        foreach ($items as &$item) {
            $item['building_id'] = $this->ensureBuilding((int) $item['building_id']);
            $item['floor_id'] = $this->ensureFloor((int) $item['floor_id'], (int) $item['building_id']);
            $item['room_id'] = $this->ensureRoom((int) $item['room_id'], (int) $item['floor_id']);
            $item['time_id'] = $this->ensureTime((int) $item['time_id']);
            $item['created_at'] = now();
            $item['updated_at'] = now();
        }

        foreach ($items as $item) {
            DB::table('class_list')->insert($item);
        }
    }

    private function ensureBuilding(int $id): int
    {
        DB::table('buildings')->updateOrInsert(
            ['id' => $id],
            [
                'id' => $id,
                'name' => "Building {$id}",
                'code' => "B{$id}",
                'description' => 'Seeded for class list testing',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return $id;
    }

    private function ensureFloor(int $id, int $buildingId): int
    {
        DB::table('floors')->updateOrInsert(
            ['id' => $id],
            [
                'id' => $id,
                'name' => "Floor {$id}",
                'level' => $id,
                'building_id' => $buildingId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return $id;
    }

    private function ensureRoom(int $id, int $floorId): int
    {
        DB::table('rooms')->updateOrInsert(
            ['id' => $id],
            [
                'id' => $id,
                'floor_id' => $floorId,
                'room_number' => "R{$id}",
                'capacity' => 30,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return $id;
    }

    private function ensureTime(int $id): int
    {
        DB::table('times')->updateOrInsert(
            ['id' => $id],
            [
                'id' => $id,
                'time_name' => "Time {$id}",
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return $id;
    }
}
