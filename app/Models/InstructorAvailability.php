<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAvailability extends Model
{
    protected $fillable = [
        'instructor_id',
        'day_of_week',
        'employment_type',
        'shift_group',
        'period',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(InstructorData::class, 'instructor_id');
    }
}
