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
                'created_at'    => now(),
                'updated_at'    => now(),
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
                'created_at'    => now(),
                'updated_at'    => now(),
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
                'created_at'    => now(),
                'updated_at'    => now(),
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
                'created_at'    => now(),
                'updated_at'    => now(),
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
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ];

        foreach ($items as $item) {
            DB::table('class_list')->insert($item);
        }
    }
}
