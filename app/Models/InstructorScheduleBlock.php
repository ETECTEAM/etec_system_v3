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
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'instructor_id' => 'integer',
            'day_of_week' => 'integer',
            'time_id' => 'integer',
            'created_by' => 'integer',
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

    // NULL = the instructor blocked their own slot from "My Availability";
    // a user = an admin / super_admin blocked it from the Instructor Busy Time grid.
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
