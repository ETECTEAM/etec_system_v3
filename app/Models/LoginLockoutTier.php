<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLockoutTier extends Model
{
    protected $fillable = [
        'offense_number',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'offense_number' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }
}
