<?php

namespace Database\Seeders\Time;

use App\Models\Time;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Time::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $times = [
            '09:00 am - 10:30 am',
            '11:00 am - 12:15 pm',
            '12:30 pm - 01:45 pm',
            '02:00 pm - 03:15 pm',
            '03:30 pm - 05:00 pm',
            '06:00 pm - 07:15 pm',
            '07:15 pm - 08:30 pm',
            '05:00 pm - 06:15 pm',
            '08:00 am - 11:00 am',
            '11:00 am - 01:30 pm',
            '11:00 am - 02:00 pm',
            '02:00 pm - 05:00 pm',
            '08:00 am - 12:00 pm',
            '12:00 pm - 05:00 pm',
            '09:00 am - 11:00 am',
            '03:30 pm - 05:30 pm',
            '05:30 pm - 07:30 pm',
        ];

        Time::insert(array_map(fn (string $timeName) => [
            'time_name' => $timeName,
            'created_at' => now(),
            'updated_at' => now(),
        ], $times));

        // truncate()/insert() bypass Eloquent events, so the cached list
        // used by TimeController::index() won't self-invalidate.
        Cache::forget(Time::CACHE_KEY);
    }
}
