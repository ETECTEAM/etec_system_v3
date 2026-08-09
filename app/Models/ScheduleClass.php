<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'title',
        'course_id',
        'lesson_id',
        'building_id',
        'floor_id',
        'room_id',
        'term_id',
        'time_id',
        'capacity',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function time(): BelongsTo
    {
        return $this->belongsTo(Time::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search) {
            return $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereRelation('course', 'title', 'like', "%{$search}%")
                  ->orWhereRelation('building', 'name', 'like', "%{$search}%")
                  ->orWhereRelation('room', 'room_number', 'like', "%{$search}%")
                  ->orWhereRelation('term', 'term_name', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function scopeSortBy(Builder $query, ?string $field, ?string $direction): Builder
    {
        $field ??= 'created_at';
        $direction ??= 'desc';

        $allowed = ['title', 'created_at', 'capacity', 'status'];

        if (in_array($field, $allowed, true)) {
            return $query->orderBy($field, $direction);
        }

        return $query->orderBy('created_at', 'desc');
    }
}
