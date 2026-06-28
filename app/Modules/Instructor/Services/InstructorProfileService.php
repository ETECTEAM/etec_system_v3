<?php

namespace App\Modules\Instructor\Services;

use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use Illuminate\Support\Facades\DB;

class InstructorProfileService
{
    public function saveProfile(int $userId, array $data): InstructorData
    {
        return DB::transaction(function () use ($userId, $data): InstructorData {
            $instructor = InstructorData::updateOrCreate(
                ['user_id' => $userId],
                [
                    'full_name' => $data['full_name'],
                    'instructor_code' => $data['instructor_code'],
                    'phone' => $data['phone'],
                    'employment_type' => $data['employment_type'],
                    'shift_group' => $data['shift_group'],
                    'available_for_class' => $data['available_for_class'] ?? true,
                    'status' => $data['status'] ?? true,
                ],
            );

            $this->generateInstructorAvailabilities($instructor);

            return $instructor->fresh('availabilities');
        });
    }

    public function generateInstructorAvailabilities(InstructorData $instructor): void
    {
        $shiftGroup = $instructor->shift_group;
        $employmentType = $instructor->employment_type;

        InstructorAvailability::where('instructor_id', $instructor->id)->delete();

        if ($shiftGroup === 'custom') {
            return;
        }

        $patterns = [
            'morning_afternoon' => [
                'days' => [1, 2, 3, 4, 5],
                'slots' => [
                    ['period' => 'daytime', 'start' => '08:00', 'end' => '17:00'],
                ],
            ],
            'morning_evening' => [
                'days' => [1, 2, 3, 4, 5],
                'slots' => [
                    ['period' => 'morning', 'start' => '08:00', 'end' => '12:00'],
                    ['period' => 'evening', 'start' => '17:00', 'end' => '20:30'],
                ],
            ],
            'weekend_morning' => [
                'days' => [6, 7],
                'slots' => [
                    ['period' => 'morning', 'start' => '08:00', 'end' => '13:30'],
                ],
            ],
            'weekend_afternoon' => [
                'days' => [6, 7],
                'slots' => [
                    ['period' => 'afternoon', 'start' => '11:00', 'end' => '17:00'],
                ],
            ],
        ];

        $pattern = $patterns[$shiftGroup] ?? null;

        if ($pattern === null) {
            return;
        }

        $availabilities = [];

        foreach ($pattern['days'] as $day) {
            foreach ($pattern['slots'] as $slot) {
                $availabilities[] = [
                    'instructor_id' => $instructor->id,
                    'day_of_week' => $day,
                    'employment_type' => $employmentType,
                    'shift_group' => $shiftGroup,
                    'period' => $slot['period'],
                    'start_time' => $slot['start'],
                    'end_time' => $slot['end'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        InstructorAvailability::insert($availabilities);
    }
}
