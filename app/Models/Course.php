<?php
// app/Models/Course.php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    /**
     * Courses that are open for enrollment: the course-wide Open/Closed toggle
     * on the Enroll Config page - the config row with no schedule and no time
     * slot - is "open", or was never set. Mirrors the master-switch rule the
     * public student-register page applies (see
     * StudentRegisterController::openClassTypesForCourse()).
     */
    public function scopeEnrollmentOpen(Builder $query): Builder
    {
        return $query->whereDoesntHave('enrollConfigs', function (Builder $config): void {
            $config->whereNull('schedule_id')
                ->whereNull('time_id')
                ->where(fn (Builder $status) => $status->whereNull('status')->orWhere('status', '!=', 'open'));
        });
    }

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
    //
    // Excludes schedule_id-scoped rows (always $0 - see schedule()) so a
    // Class Schedules availability toggle never gets picked up as the price.
    public function enrollConfigForTime(?int $timeId = null): ?CourseEnrollConfig
    {
        $priceConfigs = $this->enrollConfigs->whereNull('schedule_id');

        return $priceConfigs->first(fn (CourseEnrollConfig $config) => $config->time_id === $timeId)
            ?? $priceConfigs->first(fn (CourseEnrollConfig $config) => $config->time_id === null);
    }
}
