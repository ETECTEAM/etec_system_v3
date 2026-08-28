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
        'max_classes',
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
            'max_classes' => 'integer',
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

    /**
     * The schedule_id-scoped availability row for one class slot (course +
     * class type + term + time), or null if that slot isn't open. This is the
     * row the "max classes" badge on the Enroll Config page writes to.
     */
    public static function forClassSlot(int|string|null $courseId, int|string|null $classTypeId, int|string|null $termId, int|string|null $timeId): ?self
    {
        if (! $courseId || ! $classTypeId || ! $termId || ! $timeId) {
            return null;
        }

        $scheduleId = Schedule::query()
            ->where('class_type_id', $classTypeId)
            ->where('term_id', $termId)
            ->value('id');

        if ($scheduleId === null) {
            return null;
        }

        return static::query()
            ->where('course_id', $courseId)
            ->where('schedule_id', $scheduleId)
            ->where('time_id', $timeId)
            ->first();
    }

    /**
     * Class slots left before this availability row's max_classes is hit. Only
     * live classes (upcoming / active / pre_end) count - an ended or cancelled
     * class frees a slot. Returns null when max_classes is null (no limit) or
     * this isn't a schedule-scoped row.
     *
     * $exceptClassId drops one class from the count (the one being edited).
     * $lock takes a row lock - pass true inside the create/update transaction.
     */
    public function classSlotsRemaining(?int $exceptClassId = null, bool $lock = false): ?int
    {
        // null or 0 both mean "no limit".
        if (empty($this->max_classes) || $this->schedule_id === null) {
            return null;
        }

        $schedule = $this->relationLoaded('schedule') ? $this->schedule : $this->schedule()->first();

        if ($schedule === null) {
            return null;
        }

        $used = StudyClass::query()
            ->where('course_id', $this->course_id)
            ->where('time_id', $this->time_id)
            ->where('term_id', $schedule->term_id)
            ->where('class_type_id', $schedule->class_type_id)
            ->whereIn('status', StudyClass::LIVE_STATUSES)
            ->when($exceptClassId !== null, fn ($query) => $query->whereKeyNot($exceptClassId))
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->count();

        return max(0, $this->max_classes - $used);
    }

    public function classSlotFull(?int $exceptClassId = null, bool $lock = false): bool
    {
        $remaining = $this->classSlotsRemaining($exceptClassId, $lock);

        return $remaining !== null && $remaining <= 0;
    }

    // The actually-charged price - unit_price and course_price are both kept
    // regardless of which is selected, so switching the selection later (or
    // auditing what was charged) never loses the other figure.
    public function resolvedPrice(): float
    {
        // The "Price to Use" selector was removed - Course Price is always the
        // charged fee. Unit Price is kept on the record as a reference figure.
        return (float) $this->course_price;
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
