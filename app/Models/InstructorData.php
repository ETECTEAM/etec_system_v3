<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorData extends Model
{
    use HasFactory;

    protected $table = 'instructor_data';

    protected $fillable = [
        'user_id', 'instructor_code', 'first_name', 'last_name', 'full_name',
        'full_name_kh', 'gender', 'date_of_birth', 'phone', 'email',
        'specialization', 'employment_type', 'shift_preference',
        'available_for_class', 'hire_date', 'address', 'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'available_for_class' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
