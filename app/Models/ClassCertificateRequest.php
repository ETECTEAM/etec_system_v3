<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassCertificateRequest extends Model
{
    protected $table = 'class_certificate_requests';

    protected $fillable = [
        'study_class_id',
        'requested_by',
        'reviewed_by',
        'certificate_type',
        'status',
        'student_count',
        'note',
        'requested_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'study_class_id' => 'integer',
            'requested_by' => 'integer',
            'reviewed_by' => 'integer',
            'student_count' => 'integer',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function studyClass(): BelongsTo
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
