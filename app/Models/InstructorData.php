<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstructorData extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'instructor_code',
        'phone',
        'employment_type',
        'shift_group',
        'available_for_class',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(InstructorAvailability::class, 'instructor_id');
    }
}
