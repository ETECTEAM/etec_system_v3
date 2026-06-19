<?php

namespace Database\Seeders\Term;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TermSeeder extends Seeder
{
    public function run(): void
    {
        $fixedTerms = [
            ['term_name' => 'Mon - Tue'],
            ['term_name' => 'Wed - Thur'],
            ['term_name' => 'Mon - Thur'],
            ['term_name' => 'Friday'],
            ['term_name' => 'Saturday'],
            ['term_name' => 'Sunday'],
            ['term_name' => 'Sat - Sun'],
        ];

        foreach ($fixedTerms as $term) {
            DB::table('terms')->updateOrInsert(
                ['term_name' => $term['term_name']],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}