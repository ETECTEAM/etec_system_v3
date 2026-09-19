<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollmentPayment extends Model
{
    protected $table = 'student_enrollment_payments';

    protected $fillable = [
        'student_enrollment_id',
        'study_class_id',
        'student_id',
        'amount',
        'payment_method',
        'payment_date',
        'payment_status',
        'reference_number',
        'recorded_by',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'payment_status' => 'string',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function studyClass(): BelongsTo
    {
        return $this->belongsTo(StudyClass::class, 'study_class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
