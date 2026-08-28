<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class AccessLocationRoute extends Model
{
    protected $fillable = [
        'access_location_id',
        'route_key',
    ];

    protected function casts(): array
    {
        return [
            'access_location_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(AccessLocation::CACHE_KEY));
        static::deleted(fn () => Cache::forget(AccessLocation::CACHE_KEY));
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(AccessLocation::class, 'access_location_id');
    }
}
