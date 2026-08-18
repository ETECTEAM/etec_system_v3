<?php

namespace Database\Seeders\Schedule;

use App\Models\ShiftTemplate;
use Illuminate\Database\Seeder;

class ShiftTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $monToThu = range(1, 4); // [1, 2, 3, 4] -> Mon, Tue, Wed, Thu
        $weekend  = [6, 7];      // [6, 7]       -> Sat, Sun

        $templates = [
            // Full-time: Weekend Morning (Mon-Thu + Sat-Sun 08:00-13:30)
            [
                'name' => 'Morning & Afternoon (Mon-Thu + Weekend Morning)',
                'code' => 'morning_afternoon_am',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 09:00-17:00 + Sat-Sun 08:00-13:30, Friday off',
                'day_groups' => [
                    ['days' => $monToThu, 'period' => 'daytime', 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['days' => $weekend,  'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                ],
            ],
            [
                'name' => 'Morning & Evening (Mon-Thu + Weekend Morning)',
                'code' => 'morning_evening_am',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 09:00-12:15 & 17:00-20:30 + Sat-Sun 08:00-13:30, Friday off',
                'day_groups' => [
                    ['days' => $monToThu, 'period' => 'morning', 'start_time' => '09:00', 'end_time' => '12:00'],
                    ['days' => $monToThu, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['days' => $weekend,  'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                ],
            ],
            [
                'name' => 'Afternoon & Evening 11:00-20:30 (Mon-Thu + Weekend Morning)',
                'code' => 'afternoon_evening_11_am',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 11:00-20:30 + Sat-Sun 08:00-13:30, Friday off',
                'day_groups' => [
                    ['days' => $monToThu, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['days' => $weekend,  'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                ],
            ],
            [
                'name' => 'Afternoon & Evening 12:30-20:30 (Mon-Thu + Weekend Morning)',
                'code' => 'afternoon_evening_1230_am',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 12:30-20:30 + Sat-Sun 08:00-13:30, Friday off',
                'day_groups' => [
                    ['days' => $monToThu, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['days' => $weekend,  'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                ],
            ],

            // Full-time: Weekend Afternoon (Mon-Thu + Sat-Sun 11:00-17:00)
            [
                'name' => 'Morning & Afternoon (Mon-Thu + Weekend Afternoon)',
                'code' => 'morning_afternoon_pm',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 09:00-17:00 + Sat-Sun 11:00-17:00, Friday off',
                'day_groups' => [
                    ['days' => $monToThu, 'period' => 'daytime', 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['days' => $weekend,  'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],
            [
                'name' => 'Morning & Evening (Mon-Thu + Weekend Afternoon)',
                'code' => 'morning_evening_pm',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 09:00-12:15 & 17:00-20:30 + Sat-Sun 11:00-17:00, Friday off',
                'day_groups' => [
                    ['days' => $monToThu, 'period' => 'morning', 'start_time' => '09:00', 'end_time' => '12:00'],
                    ['days' => $monToThu, 'period' => 'evening', 'start_time' => '17:00', 'end_time' => '20:30'],
                    ['days' => $weekend,  'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],
            [
                'name' => 'Afternoon & Evening 11:00-20:30 (Mon-Thu + Weekend Afternoon)',
                'code' => 'afternoon_evening_11_pm',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 11:00-20:30 + Sat-Sun 11:00-17:00, Friday off',
                'day_groups' => [
                    ['days' => $monToThu, 'period' => 'afternoon_evening', 'start_time' => '11:00', 'end_time' => '20:30'],
                    ['days' => $weekend,  'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],
            [
                'name' => 'Afternoon & Evening 12:30-20:30 (Mon-Thu + Weekend Afternoon)',
                'code' => 'afternoon_evening_1230_pm',
                'employment_type' => 'full_time',
                'description' => 'Mon-Thu 12:30-20:30 + Sat-Sun 11:00-17:00, Friday off',
                'day_groups' => [
                    ['days' => $monToThu, 'period' => 'afternoon_evening', 'start_time' => '12:30', 'end_time' => '20:30'],
                    ['days' => $weekend,  'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],

            // Part-time
            [
                'name' => 'Weekend Morning',
                'code' => 'weekend_morning',
                'employment_type' => 'part_time',
                'description' => 'Saturday to Sunday 08:00 - 13:30',
                'day_groups' => [
                    ['days' => $weekend, 'period' => 'morning', 'start_time' => '08:00', 'end_time' => '13:30'],
                ],
            ],
            [
                'name' => 'Weekend Afternoon',
                'code' => 'weekend_afternoon',
                'employment_type' => 'part_time',
                'description' => 'Saturday to Sunday 11:00 - 17:00',
                'day_groups' => [
                    ['days' => $weekend, 'period' => 'afternoon', 'start_time' => '11:00', 'end_time' => '17:00'],
                ],
            ],
        ];

        foreach ($templates as $data) {
            $dayGroups = $data['day_groups'];
            unset($data['day_groups']);

            $template = ShiftTemplate::updateOrCreate(
                ['code' => $data['code']],
                $data
            );

            $template->blocks()->delete();

            foreach ($dayGroups as $group) {
                foreach ($group['days'] as $day) {
                    $template->blocks()->create([
                        'day_of_week' => $day,
                        'period'      => $group['period'],
                        'start_time'  => $group['start_time'],
                        'end_time'    => $group['end_time'],
                    ]);
                }
            }
        }
    }
}
