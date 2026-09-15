<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerificationSetting extends Model
{
    protected $fillable = [
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    // Singleton row: instructor registration has exactly one OTP toggle, not
    // a list, so callers always resolve id 1.
    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            ['is_enabled' => true]
        );
    }
}
