<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAvailability extends Model
{
    // 'schedule' (default) = generated from the instructor's WorkSchedule and
    // rebuilt on every profile save; 'admin' = a slot an admin opened manually
    // from the Instructor Busy Time grid, which must survive that regeneration.
    public const SOURCE_SCHEDULE = 'schedule';

    public const SOURCE_ADMIN = 'admin';

    protected $fillable = [
        'instructor_id',
        'day_of_week',
        'employment_type',
        'shift_group',
        'period',
        'start_time',
        'end_time',
        'is_active',
        'source',
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
