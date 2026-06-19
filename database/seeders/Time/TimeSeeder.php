<?php

namespace Database\Seeders\Time;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $times = [

                    // Mon - Tue
                    ['time_name' => '09:00 AM - 10:30 AM', 'term_id' => 1],
                    ['time_name' => '11:00 AM - 12:15 PM', 'term_id' => 1],
                    ['time_name' => '12:30 PM - 01:45 PM', 'term_id' => 1],
                    ['time_name' => '02:00 PM - 03:15 PM', 'term_id' => 1],
                    ['time_name' => '03:30 PM - 05:00 PM', 'term_id' => 1],
                    ['time_name' => '06:00 PM - 07:15 PM', 'term_id' => 1],
                    ['time_name' => '07:30 PM - 08:30 PM', 'term_id' => 1],

                    // Wed - Thur
                    ['time_name' => '09:00 AM - 10:30 AM', 'term_id' => 2],
                    ['time_name' => '11:00 AM - 12:15 PM', 'term_id' => 2],
                    ['time_name' => '12:30 PM - 01:45 PM', 'term_id' => 2],
                    ['time_name' => '02:00 PM - 03:15 PM', 'term_id' => 2],
                    ['time_name' => '03:30 PM - 05:00 PM', 'term_id' => 2],
                    ['time_name' => '06:00 PM - 07:15 PM', 'term_id' => 2],
                    ['time_name' => '07:30 PM - 08:30 PM', 'term_id' => 2],

                    // Mon - Thur
                    ['time_name' => '09:00 AM - 10:30 AM', 'term_id' => 3],
                    ['time_name' => '11:00 AM - 12:15 PM', 'term_id' => 3],
                    ['time_name' => '12:30 PM - 01:45 PM', 'term_id' => 3],
                    ['time_name' => '02:00 PM - 03:15 PM', 'term_id' => 3],
                    ['time_name' => '03:30 PM - 05:00 PM', 'term_id' => 3],
                    ['time_name' => '06:00 PM - 07:15 PM', 'term_id' => 3],
                    ['time_name' => '07:30 PM - 08:30 PM', 'term_id' => 3],

                    // Friday
                    ['time_name' => '08:00 AM - 11:00 AM', 'term_id' => 4],
                    ['time_name' => '11:00 AM - 02:00 PM', 'term_id' => 4],
                    ['time_name' => '02:00 PM - 05:00 PM', 'term_id' => 4],

                    // Saturday
                    ['time_name' => '08:00 AM - 11:00 AM', 'term_id' => 5],
                    ['time_name' => '11:00 AM - 02:00 PM', 'term_id' => 5],
                    ['time_name' => '02:00 PM - 05:00 PM', 'term_id' => 5],

                    // Sunday
                    ['time_name' => '08:00 AM - 11:00 AM', 'term_id' => 6],
                    ['time_name' => '11:00 AM - 02:00 PM', 'term_id' => 6],
                    ['time_name' => '02:00 PM - 05:00 PM', 'term_id' => 6],

                    // Sat - Sun
                    ['time_name' => '08:00 AM - 11:00 AM', 'term_id' => 7],
                    ['time_name' => '11:00 AM - 02:00 PM', 'term_id' => 7],
                    ['time_name' => '02:00 PM - 05:00 PM', 'term_id' => 7],
                ];

        foreach ($times as $time) {
            DB::table('times')->updateOrInsert(
                [
                    'time_name' => $time['time_name'],
                    'term_id'   => $time['term_id'],
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}