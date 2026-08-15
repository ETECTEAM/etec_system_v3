<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Building extends Model
{
    // hierarchy() has no filters/pagination, so one key covers the whole
    // building->floors->rooms tree. Floor/Room also bust this key (see their
    // bustCache()) since the tree embeds both.
    public const HIERARCHY_CACHE_KEY = 'buildings.hierarchy';

    public const HIERARCHY_CACHE_TTL = 3600;

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::bustCache());
        static::deleted(fn () => static::bustCache());
    }

    public static function bustCache(): void
    {
        Cache::forget(self::HIERARCHY_CACHE_KEY);
    }

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }
}
