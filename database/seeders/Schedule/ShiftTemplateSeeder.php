<?php

namespace Database\Seeders\Schedule;

use App\Models\ShiftTemplate;
use Illuminate\Database\Seeder;

class ShiftTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Full-time: Weekend Morning (morning + afternoon blocks)
            [
                'name' => 'Morning & Afternoon (Mon-Thu + Weekend Morning)',
                'code' => 'morning_afternoon_am',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 09:00-17:00 + Sat-Sun 08:00-13:30 & 11:00-17:00, Friday off',
                'blocks' => [
                    ['day_of_week' => 1, 'period' => 'daytime', 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['day_of_week' => 2, 'period' => 'daytime', 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['day_of_week' => 3, 'period' => 'daytime', 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['day_of_week' => 4, 'period' => 'daytime', 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['day_of_week' => 6, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                    ['day_of_week' => 6, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                    ['day_of_week' => 7, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                    ['day_of_week' => 7, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],
            [
                'name' => 'Morning & Evening (Mon-Thu + Weekend Morning)',
                'code' => 'morning_evening_am',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 09:00-12:00 & 17:00-20:30 + Sat-Sun 08:00-13:30 & 11:00-17:00, Friday off',
                'blocks' => [
                    ['day_of_week' => 1, 'period' => 'morning', 'start_time' => '09:00', 'end_time' => '12:00'],
                    ['day_of_week' => 1, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['day_of_week' => 2, 'period' => 'morning', 'start_time' => '09:00', 'end_time' => '12:00'],
                    ['day_of_week' => 2, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['day_of_week' => 3, 'period' => 'morning', 'start_time' => '09:00', 'end_time' => '12:00'],
                    ['day_of_week' => 3, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['day_of_week' => 4, 'period' => 'morning', 'start_time' => '09:00', 'end_time' => '12:00'],
                    ['day_of_week' => 4, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['day_of_week' => 6, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                    ['day_of_week' => 6, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                    ['day_of_week' => 7, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                    ['day_of_week' => 7, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],
            [
                'name' => 'Afternoon & Evening 11:00-20:30 (Mon-Thu + Weekend Morning)',
                'code' => 'afternoon_evening_11_am',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 11:00-20:30 + Sat-Sun 08:00-13:30 & 11:00-17:00, Friday off',
                'blocks' => [
                    ['day_of_week' => 1, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['day_of_week' => 2, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['day_of_week' => 3, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['day_of_week' => 4, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['day_of_week' => 6, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                    ['day_of_week' => 6, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                    ['day_of_week' => 7, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                    ['day_of_week' => 7, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],
            [
                'name' => 'Afternoon & Evening 12:30-20:30 (Mon-Thu + Weekend Morning)',
                'code' => 'afternoon_evening_1230_am',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 12:30-20:30 + Sat-Sun 08:00-13:30 & 11:00-17:00, Friday off',
                'blocks' => [
                    ['day_of_week' => 1, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['day_of_week' => 2, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['day_of_week' => 3, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['day_of_week' => 4, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['day_of_week' => 6, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                    ['day_of_week' => 6, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                    ['day_of_week' => 7, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                    ['day_of_week' => 7, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],

            // Full-time: Weekend Afternoon (afternoon blocks only)
            [
                'name' => 'Morning & Afternoon (Mon-Thu + Weekend Afternoon)',
                'code' => 'morning_afternoon_pm',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 09:00-17:00 + Sat-Sun 11:00-17:00, Friday off',
                'blocks' => [
                    ['day_of_week' => 1, 'period' => 'daytime', 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['day_of_week' => 2, 'period' => 'daytime', 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['day_of_week' => 3, 'period' => 'daytime', 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['day_of_week' => 4, 'period' => 'daytime', 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['day_of_week' => 6, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                    ['day_of_week' => 7, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],
            [
                'name' => 'Morning & Evening (Mon-Thu + Weekend Afternoon)',
                'code' => 'morning_evening_pm',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 09:00-12:00 & 17:00-20:30 + Sat-Sun 11:00-17:00, Friday off',
                'blocks' => [
                    ['day_of_week' => 1, 'period' => 'morning', 'start_time' => '09:00', 'end_time' => '12:00'],
                    ['day_of_week' => 1, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['day_of_week' => 2, 'period' => 'morning', 'start_time' => '09:00', 'end_time' => '12:00'],
                    ['day_of_week' => 2, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['day_of_week' => 3, 'period' => 'morning', 'start_time' => '09:00', 'end_time' => '12:00'],
                    ['day_of_week' => 3, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['day_of_week' => 4, 'period' => 'morning', 'start_time' => '09:00', 'end_time' => '12:00'],
                    ['day_of_week' => 4, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['day_of_week' => 6, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                    ['day_of_week' => 7, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],
            [
                'name' => 'Afternoon & Evening 11:00-20:30 (Mon-Thu + Weekend Afternoon)',
                'code' => 'afternoon_evening_11_pm',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 11:00-20:30 + Sat-Sun 11:00-17:00, Friday off',
                'blocks' => [
                    ['day_of_week' => 1, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['day_of_week' => 2, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['day_of_week' => 3, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['day_of_week' => 4, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['day_of_week' => 6, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                    ['day_of_week' => 7, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],
            [
                'name' => 'Afternoon & Evening 12:30-20:30 (Mon-Thu + Weekend Afternoon)',
                'code' => 'afternoon_evening_1230_pm',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 12:30-20:30 + Sat-Sun 11:00-17:00, Friday off',
                'blocks' => [
                    ['day_of_week' => 1, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['day_of_week' => 2, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['day_of_week' => 3, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['day_of_week' => 4, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['day_of_week' => 6, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                    ['day_of_week' => 7, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],

            // Part-time
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

            $template = ShiftTemplate::updateOrCreate(
                ['code' => $data['code']],
                $data,
            );

            $template->blocks()->delete();

            foreach ($blocks as $block) {
                $template->blocks()->create($block);
            }
        }
    }
}
