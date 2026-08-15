<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ShiftTemplate extends Model
{
    // Paginated/filtered, so results are cached per (version, page, search)
    // combination rather than under one key - see cacheKey() below.
    public const CACHE_VERSION_KEY = 'shift_templates:list:version';

    public const CACHE_TTL = 300;

    protected $fillable = [
        'name',
        'code',
        'employment_type',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::bustCache());
        static::deleted(fn () => static::bustCache());
    }

    public static function bustCache(): void
    {
        Cache::forever(self::CACHE_VERSION_KEY, self::cacheVersion() + 1);
    }

    public static function cacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 1);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ShiftTemplateBlock::class);
    }
}
