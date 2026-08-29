<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationAccessLog extends Model
{
    // One immutable row per GPS check - no updated_at.
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'path',
        'outcome',
        'access_location_id',
        'latitude',
        'longitude',
        'accuracy',
        'distance_meters',
        'ip',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'decimal:2',
            'distance_meters' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(AccessLocation::class, 'access_location_id');
    }
}
