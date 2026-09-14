<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateClassRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_class_id',
        'requested_by',
        'certificate_type',
        'status',
        'requested_at',
    ];

    protected function casts(): array
    {
        return [
            'study_class_id' => 'integer',
            'requested_by' => 'integer',
            'requested_at' => 'datetime',
        ];
    }

    public function studyClass()
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
