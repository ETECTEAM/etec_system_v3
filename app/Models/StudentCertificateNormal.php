<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentCertificateNormal extends Model
{
    protected $table = 'student_certificate_normal';

    protected $fillable = [
        'student_id',
        'study_class_id',
        'certificate_type',
        'student_name',
        'course',
        'granted_date',
        'certificate_id',
    ];
}
