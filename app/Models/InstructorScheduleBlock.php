<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorScheduleBlock extends Model
{
    public const STATUS_ACTIVE = 'active';

    protected $fillable = [
        'instructor_id',
        'day_of_week',
        'time_id',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'instructor_id' => 'integer',
            'day_of_week' => 'integer',
            'time_id' => 'integer',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(InstructorData::class, 'instructor_id');
    }

    public function time(): BelongsTo
    {
        return $this->belongsTo(Time::class);
    }
}
