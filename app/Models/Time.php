<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Time extends Model
{
    //
    public const CACHE_KEY = 'times.all';

    protected $fillable = [
        'time_name',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'schedule_time');
    }
}
