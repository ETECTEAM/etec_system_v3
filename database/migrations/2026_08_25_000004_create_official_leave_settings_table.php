<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value settings for the official-leave feature (quota, conversion, block
 * threshold, QR lifetime). Same shape as grading_settings so the cached-read
 * pattern carries over; defaults are inserted here and can be edited from the
 * super-admin Settings page.
 */
return new class extends Migration
{
    private const DEFAULTS = [
        [
            'key' => 'monthly_permission_quota',
            'value' => '4',
            'type' => 'number',
            'label' => 'Monthly permission quota',
            'description' => 'How many instructor permissions a student may use per month.',
            'min' => 0,
            'max' => 100,
        ],
        [
            'key' => 'permissions_per_absence',
            'value' => '2',
            'type' => 'number',
            'label' => 'Permissions per absence',
            'description' => 'How many used permissions convert into one equivalent absence for block counting.',
            'min' => 1,
            'max' => 20,
        ],
        [
            'key' => 'absence_block_threshold',
            'value' => '3',
            'type' => 'number',
            'label' => 'Absence block threshold',
            'description' => 'Real absences plus converted permissions at or above this value block the student.',
            'min' => 1,
            'max' => 50,
        ],
        [
            'key' => 'qr_token_ttl_minutes',
            'value' => '15',
            'type' => 'number',
            'label' => 'QR token lifetime (minutes)',
            'description' => 'How long a generated leave-request QR stays valid before it must be regenerated.',
            'min' => 1,
            'max' => 1440,
        ],
    ];

    public function up(): void
    {
        Schema::create('official_leave_settings', function (Blueprint $table) {
            $table->id();

            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->string('type', 20)->default('number');
            $table->string('label');
            $table->string('description')->nullable();
            $table->integer('min')->nullable();
            $table->integer('max')->nullable();
            $table->string('group', 50)->default('official_leave');

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });

        $now = now();

        foreach (self::DEFAULTS as $row) {
            DB::table('official_leave_settings')->insert(array_merge($row, [
                'group' => 'official_leave',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('official_leave_settings');
    }
};
