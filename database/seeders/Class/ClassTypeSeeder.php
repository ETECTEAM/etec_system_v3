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
                'type_name'     => 'Physical Class',
                'description'   => 'In-person classroom instruction',
                'is_active'     => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'class_type_id' => 2,
                'type_name'     => 'Hybrid Class',
                'description'   => 'Combination of in-person and online learning',
                'is_active'     => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'class_type_id' => 3,
                'type_name'     => 'Online Class',
                'description'   => 'Fully remote virtual learning',
                'is_active'     => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'class_type_id' => 4,
                'type_name'     => 'Basic',
                'description'   => 'Fundamental information technology courses',
                'is_active'     => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'class_type_id' => 5,
                'type_name'     => 'Scholarship Class',
                'description'   => 'Sponsored class for scholarship students',
                'is_active'     => 1,
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