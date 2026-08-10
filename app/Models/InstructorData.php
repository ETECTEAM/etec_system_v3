<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InstructorData extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'instructor_code',
        'phone',
        'specialization',
        'employment_type',
        'shift_group',
        'shift_template_id',
        'available_for_class',
        'status',
        'headline',
        'bio',
        'date_of_birth',
        'gender',
        'address',
        'telegram',
        'linkedin',
        'github',
        'portfolio_url',
    ];

    protected function casts(): array
    {
        return [
            'available_for_class' => 'boolean',
            'status' => 'boolean',
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(InstructorAvailability::class, 'instructor_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InstructorAttachment::class, 'instructor_id');
    }

    public function profilePhoto(): HasOne
    {
        return $this->hasOne(InstructorAttachment::class, 'instructor_id')
            ->where('type', 'profile_photo')
            ->where('is_primary', true);
    }

    public function cvFile(): HasOne
    {
        return $this->hasOne(InstructorAttachment::class, 'instructor_id')
            ->where('type', 'cv')
            ->where('is_primary', true);
    }
}
