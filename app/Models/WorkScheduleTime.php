<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkScheduleTime extends Model
{
    protected $fillable = [
        'work_schedule_id',
        'day_of_week',
        'time_id',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function time(): BelongsTo
    {
        return $this->belongsTo(Time::class);
    }
}
