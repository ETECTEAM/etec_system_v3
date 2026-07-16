<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLockoutSetting extends Model
{
    protected $fillable = [
        'reset_after_hours',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'reset_after_hours' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }

    // Singleton row: this feature has exactly one set of account-level
    // settings, not a list, so callers always resolve id 1.
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], ['reset_after_hours' => 24, 'is_enabled' => true]);
    }
}
