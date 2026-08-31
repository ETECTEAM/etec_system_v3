<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value tunables for the attendance-rules / absence-block feature. Same shape
 * as official_leave_settings so the cached-read helper pattern carries over;
 * defaults are seeded here and can be edited from the office Settings page.
 * config/attendance-rules.php is the fallback when a row is missing.
 */
return new class extends Migration
{
    private const DEFAULTS = [
        [
            'key' => 'absence_block_threshold',
            'value' => '3',
            'type' => 'number',
            'label' => 'Absence block threshold',
            'description' => 'Absences in the current monthly cycle at or above this value soft-lock the student.',
            'min' => 1,
            'max' => 50,
        ],
        [
            'key' => 'post_approval_limit',
            'value' => '2',
            'type' => 'number',
            'label' => 'Post-approval allowance',
            'description' => 'Extra absences allowed after the first admin approval before a hard lock is raised.',
            'min' => 1,
            'max' => 20,
        ],
        [
            'key' => 'permission_weekly_limit',
            'value' => '1',
            'type' => 'number',
            'label' => 'Weekly permission limit',
            'description' => 'Manual permissions a student may use per ISO week before extra ones count as absence.',
            'min' => 0,
            'max' => 20,
        ],
        [
            'key' => 'cycle_anchor_date',
            'value' => '2026-04-01',
            'type' => 'string',
            'label' => 'Cycle anchor date',
            'description' => 'Earliest date any absence cycle can start from.',
            'min' => null,
            'max' => null,
        ],
    ];

    public function up(): void
    {
        Schema::create('attendance_rule_settings', function (Blueprint $table) {
            $table->id();

            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->string('type', 20)->default('number');
            $table->string('label');
            $table->string('description')->nullable();
            $table->integer('min')->nullable();
            $table->integer('max')->nullable();
            $table->string('group', 50)->default('attendance_rules');

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        $now = now();

        foreach (self::DEFAULTS as $row) {
            DB::table('attendance_rule_settings')->insert(array_merge($row, [
                'group' => 'attendance_rules',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_rule_settings');
    }
};
