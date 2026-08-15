<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Floor extends Model
{
    // Paginated/filtered, so results are cached per (version, page, search)
    // combination rather than under one key.
    public const CACHE_VERSION_KEY = 'floors:list:version';

    public const CACHE_TTL = 300;

    protected $fillable = [
        'building_id',
        'name',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'building_id' => 'integer',
            'level' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::bustCache());
        static::deleted(fn () => static::bustCache());
    }

    // Floor sits inside Building::hierarchy()'s tree too, so busting the
    // floor list also busts the building hierarchy cache. BuildingService
    // calls this explicitly wherever it bulk-deletes floors (relation
    // ->delete() doesn't fire model events, so booted() alone can't catch
    // that path).
    public static function bustCache(): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, self::cacheVersion() + 1);
        Building::bustCache();
    }

    public static function cacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 1);
    }

    public function building(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function rooms(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Room::class);
    }
}
