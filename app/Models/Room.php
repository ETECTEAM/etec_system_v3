<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Room extends Model
{
    // Paginated/filtered, so results are cached per (version, page, search)
    // combination rather than under one key.
    public const CACHE_VERSION_KEY = 'rooms:list:version';

    public const CACHE_TTL = 300;

    protected $table = 'rooms';
    protected $fillable = [
        'floor_id',
        'room_number',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'floor_id' => 'integer',
            'capacity' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::bustCache());
        static::deleted(fn () => static::bustCache());
    }

    // Room sits inside Building::hierarchy()'s tree too, so busting the room
    // list also busts the building hierarchy cache. BuildingService calls
    // this explicitly wherever it bulk-deletes rooms (relation ->delete()
    // doesn't fire model events, so booted() alone can't catch that path).
    public static function bustCache(): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, self::cacheVersion() + 1);
        Building::bustCache();
    }

    public static function cacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 1);
    }

    public function floor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }
}

