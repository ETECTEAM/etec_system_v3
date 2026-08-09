<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLockout extends Model
{
    protected $fillable = [
        'login',
        'offense_number',
        'last_offense_at',
        'banned_until',
        'is_hard_block',
    ];

    protected function casts(): array
    {
        return [
            'offense_number' => 'integer',
            'last_offense_at' => 'datetime',
            'banned_until' => 'datetime',
            'is_hard_block' => 'boolean',
        ];
    }
}
