<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseEnrollConfig extends Model
{
    use HasFactory;

    public const PRICE_TYPE_UNIT = 'unit';

    public const PRICE_TYPE_COURSE = 'course';

    protected $fillable = [
        'course_id',
        'schedule_id',
        'time_id',
        'status',
        'start_date',
        'unit_price',
        'course_price',
        'selected_price_type',
        'document_price',
    ];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'schedule_id' => 'integer',
            'time_id' => 'integer',
            'start_date' => 'date',
            'unit_price' => 'decimal:2',
            'course_price' => 'decimal:2',
            'document_price' => 'decimal:2',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // NULL on rows that predate the Class Schedules picker.
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function time()
    {
        return $this->belongsTo(Time::class);
    }

    // The actually-charged price - unit_price and course_price are both kept
    // regardless of which is selected, so switching the selection later (or
    // auditing what was charged) never loses the other figure.
    public function resolvedPrice(): float
    {
        return $this->selected_price_type === self::PRICE_TYPE_UNIT
            ? (float) $this->unit_price
            : (float) $this->course_price;
    }

    /**
     * The pricing config that applies to a class: the row scoped to $timeId if
     * one exists, otherwise the course-wide (time_id NULL) row. schedule_id-
     * scoped rows are excluded - they're availability toggles, always $0 (see
     * schedule()).
     *
     * The course-wide fallback matters: a course priced only at the course
     * level (a single time_id NULL row) would otherwise resolve to no config
     * for any class that carries a time_id, zeroing the fee. Mirrors
     * Course::enrollConfigForTime().
     */
    public static function forCourseTime(int|string $courseId, int|string|null $timeId): ?self
    {
        $courseId = (int) $courseId;
        $timeId = $timeId === null ? null : (int) $timeId;

        $priceConfigs = fn () => static::query()
            ->where('course_id', $courseId)
            ->whereNull('schedule_id');

        if ($timeId !== null) {
            $specific = $priceConfigs()->where('time_id', $timeId)->first();

            if ($specific !== null) {
                return $specific;
            }
        }

        return $priceConfigs()->whereNull('time_id')->first();
    }
}
