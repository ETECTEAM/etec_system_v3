<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_AUTO = 'auto';

    public const SOURCE_ADMIN_EDIT = 'admin_edit';

    public const SOURCE_QR = 'qr_code';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_PERMISSION = 'permission';

    public const STATUS_LATE = 'late';

    /** Boolean columns a status label toggles. All false = pending. */
    private const STATUS_FLAGS = [self::STATUS_PRESENT, self::STATUS_ABSENT, self::STATUS_PERMISSION, self::STATUS_LATE];

    protected $fillable = [
        'study_class_id',
        'student_enrollment_id',
        'attendance_session_id',
        'student_id',
        'tracked_by',
        'attendance_date',
        'latitude',
        'longitude',
        'location_accuracy',
        'distance_from_class',
        'ip_address',
        'user_agent',
        'browser',
        'operating_system',
        'device_type',
        'device_identifier',
        'present',
        'absent',
        'permission',
        'late',
        'locked',
        'lock_reason',
        'locked_block_id',
        'verification_status',
        'verification_reason',
        'source',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'study_class_id' => 'integer',
            'student_enrollment_id' => 'integer',
            'attendance_session_id' => 'integer',
            'student_id' => 'integer',
            'tracked_by' => 'integer',
            'attendance_date' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'location_accuracy' => 'decimal:2',
            'distance_from_class' => 'decimal:2',
            'present' => 'boolean',
            'absent' => 'boolean',
            'permission' => 'boolean',
            'late' => 'boolean',
            'locked' => 'boolean',
            'locked_block_id' => 'integer',
        ];
    }

    public function attendanceSession()
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function studyClass()
    {
        return $this->belongsTo(StudyClass::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function tracker()
    {
        return $this->belongsTo(User::class, 'tracked_by');
    }

    public function lockedBlock()
    {
        return $this->belongsTo(StudentAttendanceBlock::class, 'locked_block_id');
    }

    /** One of the four flag columns set to true, or 'pending' if none are. */
    public function statusLabel(): string
    {
        foreach (self::STATUS_FLAGS as $flag) {
            if ($this->{$flag}) {
                return $flag;
            }
        }

        return self::STATUS_PENDING;
    }

    /**
     * Boolean-flag attributes for a status label, e.g. flagsFor('absent') =>
     * ['present' => false, 'absent' => true, 'permission' => false, 'late' => false].
     * Pass 'pending' (or any non-flag value) to clear every flag.
     */
    public static function flagsFor(string $status): array
    {
        return array_combine(self::STATUS_FLAGS, array_map(
            static fn (string $flag): bool => $flag === $status,
            self::STATUS_FLAGS
        ));
    }

    /**
     * Same as statusLabel(), for plain DB::table() rows (stdClass/array) that
     * aren't hydrated into this model - e.g. raw query results.
     */
    public static function labelForFlags(object|array $row): string
    {
        $row = (array) $row;

        foreach (self::STATUS_FLAGS as $flag) {
            if (! empty($row[$flag])) {
                return $flag;
            }
        }

        return self::STATUS_PENDING;
    }
}
