<?php

namespace Database\Seeders;

use App\Models\GradingSetting;
use Illuminate\Database\Seeder;

class GradingSettingSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'key' => 'attendance.auto_record_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'Auto-record attendance',
                'description' => 'When on, a class with no attendance submitted by the instructor is recorded automatically after the grace period below.',
                'group' => 'attendance',
            ],
            [
                'key' => 'attendance.auto_record_grace_minutes',
                'value' => '15',
                'type' => 'number',
                'label' => 'Grace minutes',
                'description' => 'Minutes after a class starts before the system records attendance on the instructor\'s behalf.',
                'min' => 1,
                // No max: validated dynamically against the shortest configured class
                // duration in SaveAttendanceSettingsRequest, which changes over time.
                'group' => 'attendance',
            ],
            [
                'key' => 'attendance.auto_record_default_status',
                'value' => 'present',
                'type' => 'string',
                'label' => 'Default status',
                'description' => 'Status auto-recorded students receive. Never "absent" - a student must not fail because an instructor forgot to submit.',
                'group' => 'attendance',
            ],
            [
                'key' => 'attendance.auto_record_notify_instructor',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'Notify instructor',
                'description' => 'Show the instructor a banner on the class the next time they open it after an auto-record.',
                'group' => 'attendance',
            ],
            [
                'key' => 'attendance.auto_record_allow_override',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'Allow instructor override',
                'description' => 'Lets the instructor correct an auto-recorded session within the override window below.',
                'group' => 'attendance',
            ],
            [
                'key' => 'attendance.auto_record_override_hours',
                'value' => '24',
                'type' => 'number',
                'label' => 'Override window (hours)',
                'description' => 'How long after an auto-record the instructor may still correct it.',
                'min' => 1,
                'group' => 'attendance',
            ],
        ];

        foreach ($rows as $row) {
            GradingSetting::updateOrCreate(
                ['key' => $row['key']],
                $row,
            );
        }
    }
}
