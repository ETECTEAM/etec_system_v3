<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    use HasFactory;

    protected $table = 'student_enrollments';

    protected $fillable = [
        'study_class_id',
        'student_id',
        'enrollment_status',
        'payment_status',
        'source',
        'fee_amount',
        'document_fee_amount',
        'amount_paid',
        'enrolled_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'study_class_id' => 'integer',
            'student_id' => 'integer',
            'fee_amount' => 'decimal:2',
            'document_fee_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'enrolled_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function studyClass()
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
