<?php

namespace Database\Seeders\Term;

use App\Models\Term;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Term::query()->delete();

        Term::insert([
            [
                'term_name' => 'Term 1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'term_name' => 'Term 2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'term_name' => 'Term 3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
