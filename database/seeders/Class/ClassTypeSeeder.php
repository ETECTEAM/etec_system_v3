<?php

namespace Database\Seeders\Class; 

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'class_type_id' => 1,
                'type_name'     => 'Full-Time',
                'description'   => 'Monday to Friday regular classes',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'class_type_id' => 2,
                'type_name'     => 'Part-Time',
                'description'   => 'Evening shifts for working students',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'class_type_id' => 3,
                'type_name'     => 'Short Course',
                'description'   => 'Specialized skills training courses',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'class_type_id' => 4,
                'type_name'     => 'Weekend Class',
                'description'   => 'Saturday and Sunday sessions only',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ];

        foreach ($types as $type) {
            DB::table('class_type')->updateOrInsert(
                ['class_type_id' => $type['class_type_id']],
                $type
            );
        }
    }
}