<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_enrollment_id',
        'study_class_id',
        'student_id',
        'attendance_score',
        'activity_score',
        'exam_score',
    ];

    protected function casts(): array
    {
        return [
            'student_enrollment_id' => 'integer',
            'study_class_id' => 'integer',
            'student_id' => 'integer',
            'attendance_score' => 'decimal:2',
            'activity_score' => 'decimal:2',
            'exam_score' => 'decimal:2',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function studyClass(): BelongsTo
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
