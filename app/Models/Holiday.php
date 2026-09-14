<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Holiday extends Model
{
    protected $fillable = [
        'group_id',
        'date',
        'name',
        'start_date',
        'end_date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public static function normalizeDate($date): string
    {
        return Carbon::parse($date, 'Asia/Phnom_Penh')->toDateString();
    }

    public static function isHoliday($date): bool
    {
        return static::query()
            ->whereDate('date', static::normalizeDate($date))
            ->exists();
    }

    public static function datesBetween($startDate, $endDate): Collection
    {
        $start = Carbon::parse($startDate, 'Asia/Phnom_Penh')->startOfDay();
        $end = Carbon::parse($endDate ?? $startDate, 'Asia/Phnom_Penh')->startOfDay();
        $dates = collect();

        for ($date = $start->copy(); $date->lessThanOrEqualTo($end); $date->addDay()) {
            $dates->push($date->toDateString());
        }

        return $dates;
    }

    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('date', [
            static::normalizeDate($startDate),
            static::normalizeDate($endDate),
        ]);
    }
}
