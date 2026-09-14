<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Absence block threshold" and "Permissions per absence" on the Leave Settings
 * page were dead - no code reads official_leave_settings for them. The live
 * absence-block tuning lives in attendance_rule_settings (Absence Blocks ->
 * Rule Settings). Dropping the rows here leaves Leave Settings with just what
 * it actually controls: the monthly permission quota and the QR link lifetime.
 */
return new class extends Migration
{
    private const DEAD_KEYS = ['permissions_per_absence', 'absence_block_threshold'];

    public function up(): void
    {
        DB::table('official_leave_settings')->whereIn('key', self::DEAD_KEYS)->delete();
    }

    public function down(): void
    {
        $now = now();

        DB::table('official_leave_settings')->insertOrIgnore([
            [
                'key' => 'permissions_per_absence',
                'value' => '2',
                'type' => 'number',
                'label' => 'Permissions per absence',
                'description' => 'How many used permissions convert into one equivalent absence for block counting.',
                'min' => 1,
                'max' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'absence_block_threshold',
                'value' => '3',
                'type' => 'number',
                'label' => 'Absence block threshold',
                'description' => 'Real absences plus converted permissions at or above this value block the student.',
                'min' => 1,
                'max' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
};
