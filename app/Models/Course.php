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
        'level',
        'thumbnail',
        'status',
        'enroll_order',
    ];

    public function track()
    {
        return $this->belongsTo(CourseTrack::class, 'course_track_id');
    }

    public function lessons()
    {
        return $this->hasMany(CourseLesson::class);
    }

    // The course's default (no time slot) enrollment config - kept for readers
    // that predate per-time-slot schedules. Prefer enrollConfigs()/enrollConfigForTime().
    public function enrollConfig()
    {
        return $this->hasOne(CourseEnrollConfig::class)->whereNull('time_id');
    }

    // Every enrollment schedule the course offers - one config row per time slot.
    public function enrollConfigs()
    {
        return $this->hasMany(CourseEnrollConfig::class);
    }

    // The config that applies to a given time slot: the time-specific config if
    // one exists, otherwise the course's default (time_id NULL) config.
    public function enrollConfigForTime(?int $timeId = null): ?CourseEnrollConfig
    {
        return $this->enrollConfigs
            ->first(fn (CourseEnrollConfig $config) => $config->time_id === $timeId)
            ?? $this->enrollConfigs->first(fn (CourseEnrollConfig $config) => $config->time_id === null);
    }
}
