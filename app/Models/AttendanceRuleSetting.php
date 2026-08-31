<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value tunables for the attendance-rules / absence-block feature. Mirrors
 * OfficialLeaveSetting; read through the attendance_rule_setting() helper.
 *
 * @see database/migrations/2026_08_31_000003_create_attendance_rule_settings_table.php
 */
class AttendanceRuleSetting extends Model
{
    public const CACHE_KEY = 'attendance_rule_settings.all';

    protected $fillable = [
        'key',
        'value',
        'type',
        'label',
        'description',
        'min',
        'max',
        'group',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'min' => 'integer',
            'max' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
