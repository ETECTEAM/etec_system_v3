<?php

namespace Database\Seeders\Term;

use App\Models\Term;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Term::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Term::insert([
            [
                'term_name' => 'Sunday',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'term_name' => 'Saturday',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'term_name' => 'Wed & Thu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'term_name' => 'Mon & Tue',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'term_name' => 'Sat & Sun',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'term_name' => 'Mon & Thu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
