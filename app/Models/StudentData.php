<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentData extends Model
{
    use HasFactory;

    protected $table = 'student_data';

    protected $fillable = [
        'user_id', 'student_code', 'first_name', 'last_name', 'full_name',
        'full_name_kh', 'gender', 'date_of_birth', 'phone', 'email', 'class_id',
        'parent_name', 'parent_phone', 'address', 'status',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'status' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
