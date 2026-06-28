<?php

namespace Database\Seeders;

use App\Models\ShiftTemplate;
use Illuminate\Database\Seeder;

class ShiftTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Morning & Afternoon',
                'code' => 'morning_afternoon',
                'employment_type' => 'full_time',
                'description' => 'Monday to Friday 08:00 - 17:00',
                'blocks' => [
                    ['day_of_week' => 1, 'period' => 'daytime', 'start_time' => '08:00', 'end_time' => '17:00'],
                    ['day_of_week' => 2, 'period' => 'daytime', 'start_time' => '08:00', 'end_time' => '17:00'],
                    ['day_of_week' => 3, 'period' => 'daytime', 'start_time' => '08:00', 'end_time' => '17:00'],
                    ['day_of_week' => 4, 'period' => 'daytime', 'start_time' => '08:00', 'end_time' => '17:00'],
                    ['day_of_week' => 5, 'period' => 'daytime', 'start_time' => '08:00', 'end_time' => '17:00'],
                ],
            ],
            [
                'name' => 'Morning & Evening',
                'code' => 'morning_evening',
                'employment_type' => 'full_time',
                'description' => 'Monday to Friday: Morning 08:00-12:00, Evening 17:00-20:30',
                'blocks' => [
                    ['day_of_week' => 1, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '12:00'],
                    ['day_of_week' => 1, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['day_of_week' => 2, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '12:00'],
                    ['day_of_week' => 2, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['day_of_week' => 3, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '12:00'],
                    ['day_of_week' => 3, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['day_of_week' => 4, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '12:00'],
                    ['day_of_week' => 4, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['day_of_week' => 5, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '12:00'],
                    ['day_of_week' => 5, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                ],
            ],
            [
                'name' => 'Afternoon & Evening 11:00-20:30',
                'code' => 'afternoon_evening_11',
                'employment_type' => 'full_time',
                'description' => 'Monday to Friday 11:00 - 20:30',
                'blocks' => [
                    ['day_of_week' => 1, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['day_of_week' => 2, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['day_of_week' => 3, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['day_of_week' => 4, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['day_of_week' => 5, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                ],
            ],
            [
                'name' => 'Afternoon & Evening 12:30-20:30',
                'code' => 'afternoon_evening_1230',
                'employment_type' => 'full_time',
                'description' => 'Monday to Friday 12:30 - 20:30',
                'blocks' => [
                    ['day_of_week' => 1, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['day_of_week' => 2, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['day_of_week' => 3, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['day_of_week' => 4, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['day_of_week' => 5, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                ],
            ],
            [
                'name' => 'Weekend Morning',
                'code' => 'weekend_morning',
                'employment_type' => 'part_time',
                'description' => 'Saturday to Sunday 08:00 - 13:30',
                'blocks' => [
                    ['day_of_week' => 6, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                    ['day_of_week' => 7, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                ],
            ],
            [
                'name' => 'Weekend Afternoon',
                'code' => 'weekend_afternoon',
                'employment_type' => 'part_time',
                'description' => 'Saturday to Sunday 11:00 - 17:00',
                'blocks' => [
                    ['day_of_week' => 6, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                    ['day_of_week' => 7, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],
        ];

        foreach ($templates as $data) {
            $blocks = $data['blocks'];
            unset($data['blocks']);

            $template = ShiftTemplate::firstOrCreate(
                ['code' => $data['code']],
                $data,
            );

            if ($template->wasRecentlyCreated) {
                foreach ($blocks as $block) {
                    $template->blocks()->create($block);
                }
            }
        }
    }
}
