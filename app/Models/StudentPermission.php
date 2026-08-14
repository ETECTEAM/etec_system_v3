<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'study_class_id',
        'start_date',
        'end_date',
        'reason',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'study_class_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_by' => 'integer',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function studyClass()
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
