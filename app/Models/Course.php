<?php
// app/Models/Course.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_track_id',
        'title',
        'slug',
        'description',
        'level',
        'duration',
        'price',
        'document_price',
        'thumbnail',
        'language',
        'certificate_available',
        'status'
    ];

    protected $casts = [
        'certificate_available' => 'boolean',
        'duration' => 'integer',
        'price' => 'decimal:2',
        'document_price' => 'decimal:2',
    ];

    public function track()
    {
        return $this->belongsTo(CourseTrack::class, 'course_track_id');
    }

    public function lessons()
    {
        return $this->hasMany(CourseLesson::class);
    }
}
