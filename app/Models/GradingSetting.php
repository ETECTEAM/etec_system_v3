<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GradingSetting extends Model
{
    use HasFactory;

    public const CACHE_KEY = 'grading_settings.all';

    protected $table = 'grading_settings';

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
            'min' => 'decimal:2',
            'max' => 'decimal:2',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(GradingSetting::CACHE_KEY));
        static::deleted(fn () => Cache::forget(GradingSetting::CACHE_KEY));
    }
}
