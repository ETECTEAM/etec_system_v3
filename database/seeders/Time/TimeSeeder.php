<?php

namespace Database\Seeders\Time;

use App\Models\Term;
use App\Models\Time;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $termId = $this->getTermId('Term 1');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Time::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Time::insert([
            [
                'time_name' => 'Mon-Thu (09:00 AM - 10:30 AM)',
                'term_id' => $termId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'time_name' => 'Mon-Thu (11:00 AM - 12:15 PM)',
                'term_id' => $termId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'time_name' => 'Mon-Thu (12:30 AM - 1:45 PM)',
                'term_id' => $termId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'time_name' => 'Mon-Thu (2:00 AM - 3:15 PM)',
                'term_id' => $termId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'time_name' => 'Mon-Thu (3:30 AM - 5:00 PM)',
                'term_id' => $termId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'time_name' => 'Mon-Thu (5:00 AM - 6:00 PM)',
                'term_id' => $termId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'time_name' => 'Mon-Thu (6:00 AM - 7:15 PM)',
                'term_id' => $termId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'time_name' => 'Mon-Thu (7:30 AM - 8:30 PM)',
                'term_id' => $termId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'time_name' => 'Sat-Sun (08:00 AM - 11:00 AM)',
                'term_id' => $termId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'time_name' => 'Sat-Sun (11:00 AM - 1:30 AM)',
                'term_id' => $termId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'time_name' => 'Sat-Sun (02:00 PM - 05:00 PM)',
                'term_id' => $termId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function getTermId($termName)
    {
        $term = Term::where('term_name', $termName)->first();

        if (! $term) {
            throw new \RuntimeException("Term '{$termName}' not found. Run TermSeeder first.");
        }

        return $term->id;
    }
}
