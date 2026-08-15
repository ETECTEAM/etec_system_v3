<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Term extends Model
{
    //
    public const CACHE_KEY = 'terms.all';

    protected $fillable = ['term_name'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function times()
    {
        return $this->hasMany(Time::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
