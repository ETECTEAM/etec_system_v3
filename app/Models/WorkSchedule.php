<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class WorkSchedule extends Model
{
    public const CACHE_VERSION_KEY = 'work_schedules:list:version';
    public const CACHE_TTL = 300;

    protected $fillable = [
        'name',
        'code',
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

    public function times(): HasMany
    {
        return $this->hasMany(WorkScheduleTime::class);
    }

    public function instructors(): HasMany
    {
        return $this->hasMany(InstructorData::class, 'work_schedule_id');
    }

    /**
     * Check if this work schedule covers a specific day_of_week + time_id.
     */
    public function covers(int $dayOfWeek, int $timeId): bool
    {
        return $this->times()
            ->where('day_of_week', $dayOfWeek)
            ->where('time_id', $timeId)
            ->exists();
    }
}
