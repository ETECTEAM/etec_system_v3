<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class OfficialLeaveSetting extends Model
{
    public const CACHE_KEY = 'official_leave_settings.all';

    protected $fillable = [
        'key',
        'value',
        'type',
        'label',
        'description',
        'min',
        'max',
        'group',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'min' => 'integer',
            'max' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
