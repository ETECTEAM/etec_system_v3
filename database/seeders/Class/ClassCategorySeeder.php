<?php

namespace Database\Seeders\Class;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'category_code' => 'E001', 
                'category_name' => 'PHP + Laravel',
                'class_type_id' => 3, 
                'description'   => 'Master backend web development with Laravel framework',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'category_code' => 'E002',
                'category_name' => 'C++ Programming',
                'class_type_id' => 3,
                'description'   => 'Introduction to C++ and Object-Oriented Programming',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'category_code' => 'E003',
                'category_name' => 'Web Design + React.js',
                'class_type_id' => 3,
                'description'   => 'Build modern user interfaces with React and Tailwind CSS',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'category_code' => 'E004', 
                'category_name' => 'Python + Flask',
                'class_type_id' => 4,
                'description'   => 'Weekend intensive course on API development with Flask',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'category_code' => 'E005', 
                'category_name' => 'UI/UX Design with Figma',
                'class_type_id' => 4,
                'description'   => 'Learn professional wireframing and prototyping',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ];

        foreach ($categories as $category) {
            DB::table('class_category')->updateOrInsert(
                ['category_name' => $category['category_name']],
                $category
            );
        }
    }
}