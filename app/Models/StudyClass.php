<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyClass extends Model
{
    use HasFactory;

    protected $table = 'study_classes';

    protected $fillable = [
        'title',
        'course_id',
        'lesson_id',
        'teacher_id',
        'room_id',
        'class_type_id',
        'term_id',
        'time_id',
        'status',
        'capacity',
        'price',
        'document_price',
        'enrollment_start_date',
        'start_date',
        'end_date',
        'meeting_link',
    ];

    protected function casts(): array
    {
        return [
            'course_id' => 'integer',
            'lesson_id' => 'integer',
            'teacher_id' => 'integer',
            'room_id' => 'integer',
            'class_type_id' => 'integer',
            'term_id' => 'integer',
            'time_id' => 'integer',
            'capacity' => 'integer',
            'price' => 'decimal:2',
            'document_price' => 'decimal:2',
            'enrollment_start_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson()
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function classType()
    {
        return $this->belongsTo(ClassType::class, 'class_type_id', 'class_type_id');
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function time()
    {
        return $this->belongsTo(Time::class);
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    // ─── Derived schedule values ───────────────────────────────────────────
    // study_days / start_time / end_time are no longer stored; they are derived
    // from the related term (name encodes the days) and time (name encodes the
    // range). The accessors keep any legacy reader of the old columns working.

    protected function studyDays(): Attribute
    {
        return Attribute::make(get: fn () => $this->scheduleStudyDays());
    }

    protected function startTime(): Attribute
    {
        return Attribute::make(get: fn () => $this->scheduleStartTime());
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(get: fn () => $this->scheduleEndTime());
    }

    public function scheduleStudyDays(): array
    {
        return $this->parseTermDays($this->term?->term_name);
    }

    public function scheduleStartTime(): ?string
    {
        return $this->timeRange()['start'] ?? null;
    }

    public function scheduleEndTime(): ?string
    {
        return $this->timeRange()['end'] ?? null;
    }

    public function isOnline(): bool
    {
        return str_contains(strtolower($this->classType?->type_name ?? ''), 'online');
    }

    public function classTypeValue(): string
    {
        return $this->isOnline() ? 'online' : 'physical';
    }

    private function parseTermDays(?string $termName): array
    {
        $dayMap = [
            'Mon' => 'Monday', 'Monday' => 'Monday',
            'Tue' => 'Tuesday', 'Tues' => 'Tuesday', 'Tuesday' => 'Tuesday',
            'Wed' => 'Wednesday', 'Wednesday' => 'Wednesday',
            'Thu' => 'Thursday', 'Thur' => 'Thursday', 'Thurs' => 'Thursday', 'Thursday' => 'Thursday',
            'Fri' => 'Friday', 'Friday' => 'Friday',
            'Sat' => 'Saturday', 'Saturday' => 'Saturday',
            'Sun' => 'Sunday', 'Sunday' => 'Sunday',
        ];

        return collect(preg_split('/\s*(?:-|,|&|\/|\+|and)\s*/i', (string) $termName))
            ->map(fn (string $day) => $dayMap[trim($day)] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function timeRange(): array
    {
        $range = preg_match('/\(([^)]+)\)/', (string) $this->time?->time_name, $match)
            ? $match[1]
            : $this->time?->time_name;

        [$start, $end] = array_pad(preg_split('/\s*-\s*/', trim((string) $range)), 2, null);

        return [
            'start' => $this->toHm($start),
            'end' => $this->toHm($end),
        ];
    }

    private function toHm(?string $time): ?string
    {
        $time = trim((string) $time);

        if ($time === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $time, $match)) {
            $hour = (int) $match[1];
            $minute = (int) $match[2];
            $meridiem = strtoupper($match[3]);

            if ($meridiem === 'AM' && $hour === 12) {
                $hour = 0;
            }
            if ($meridiem === 'PM' && $hour !== 12) {
                $hour += 12;
            }

            return sprintf('%02d:%02d', $hour, $minute);
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $match)) {
            return sprintf('%02d:%02d', (int) $match[1], (int) $match[2]);
        }

        return null;
    }
}
