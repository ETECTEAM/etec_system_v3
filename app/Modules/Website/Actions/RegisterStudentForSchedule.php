<?php

namespace App\Modules\Website\Actions;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\InstructorData;
use App\Models\Notification;
use App\Models\PendingRegistration;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Modules\Enroll\Services\StudentRegistrationService;
use App\Modules\Notification\Events\NotificationsUpdated;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use stdClass;

class RegisterStudentForSchedule
{
    private const DEFAULT_CAPACITY = 12;
    private const OPEN_CLASS_STATUSES = ['upcoming', 'active', 'pre_end'];

    public function __construct(private readonly StudentRegistrationService $registrations) {}

    public function handle(array $data): ?StudentEnrollment
    {
        $enrollment = DB::transaction(function () use ($data): ?StudentEnrollment {
            $course = Course::query()->lockForUpdate()->findOrFail($data['course_id']);
            $student = $this->registrations->findOrCreatePublicStudent($data);

            if ($this->registrations->activeEnrollmentExistsForCourseSchedule($student->id, $course->id, $data['term_id'], $data['time_id'])) {
                throw ValidationException::withMessages([
                    'phone' => 'You are already registered in that class.',
                ]);
            }

            $studyClass = $this->availableClass($course, $data)
                ?? $this->createClass($course, $data);

            if ($studyClass === null) {
                $this->savePendingRegistration($student, $course, $data);

                return null;
            }

            $enrollment = StudentEnrollment::create([
                'study_class_id' => $studyClass->id,
                'student_id' => $student->id,
                'source' => 'public_website',
                'fee_amount' => $studyClass->price,
                'document_fee_amount' => $studyClass->document_price,
                'enrolled_at' => now(),
            ]);

            DB::table('notifications')->insert([
                'title' => 'New Student Registration',
                'message' => "{$data['name']} registered for \"{$studyClass->title}\".",
                'type' => 'class_registration',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $enrollment;
        });

        NotificationsUpdated::dispatch();

        return $enrollment;
    }

    // No open class had space, and creating a new one wasn't possible (no
    // free room and/or no free instructor for that term/time), so the
    // student/course/term/time is parked here instead of silently creating a
    // roomless/teacherless class. Staff resolve it manually by force-adding
    // the student to an existing class (see EnrollStudent).
    private function savePendingRegistration(stdClass $student, Course $course, array $data): void
    {
        PendingRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'term_id' => $data['term_id'],
            'time_id' => $data['time_id'],
            'status' => 'pending',
        ]);

        Notification::create([
            'title' => 'Registration needs manual scheduling',
            'message' => "{$data['name']} wants to join \"{$course->title}\" but no room or instructor is available for that term and time. Assign them to a class manually.",
            'type' => 'pending_registration',
        ]);
    }

    private function availableClass(Course $course, array $data): ?StudyClass
    {
        $classes = StudyClass::query()
            ->where('course_id', $course->id)
            ->where('term_id', $data['term_id'])
            ->where('time_id', $data['time_id'])
            ->whereIn('status', self::OPEN_CLASS_STATUSES)
            ->orderBy('id')
            ->pluck('id');

        foreach ($classes as $classId) {
            $studyClass = StudyClass::query()->lockForUpdate()->find($classId);

            if ($studyClass === null) {
                continue;
            }

            $activeCount = $studyClass->enrollments()
                ->where('enrollment_status', 'active')
                ->count();

            if ($activeCount < $studyClass->capacity) {
                $this->ensureClassAssignments($studyClass, $course, $data);

                return $studyClass;
            }
        }

        return null;
    }

    private function ensureClassAssignments(StudyClass $studyClass, Course $course, array $data): void
    {
        $classTypeId = $this->hasScheduleFor($studyClass->class_type_id, $data)
            ? $studyClass->class_type_id
            : $this->classTypeId($data, $studyClass);
        $updates = [];

        if ($studyClass->class_type_id !== $classTypeId && $classTypeId !== null) {
            $updates['class_type_id'] = $classTypeId;
        }

        if ($studyClass->teacher_id === null) {
            $teacher = $this->availableInstructor($course, $data);

            if ($teacher !== null) {
                $updates['teacher_id'] = $teacher->user_id;
            }
        }

        if ($studyClass->room_id === null && ! $this->isOnline($classTypeId)) {
            $room = $this->availableRoom($data, (int) ($studyClass->capacity ?: self::DEFAULT_CAPACITY));

            if ($room !== null) {
                $updates['room_id'] = $room->id;
                $updates['capacity'] = $room->capacity ?: $studyClass->capacity;
            }
        }

        if ($updates) {
            $studyClass->update($updates);
            $studyClass->refresh();
        }
    }

    // Returns null (instead of creating a roomless/teacherless class) when a
    // physical room is required but none is free, or no instructor is free,
    // for this term/time.
    private function createClass(Course $course, array $data): ?StudyClass
    {
        $course->loadMissing('track.subCategory.category', 'enrollConfig');

        $defaults = StudyClass::query()
            ->where('course_id', $course->id)
            ->latest('id')
            ->first();

        $classTypeId = $this->classTypeId($data, $defaults) ?? $defaults?->class_type_id;
        $baseCapacity = $defaults?->capacity ?: self::DEFAULT_CAPACITY;
        $isOnline = $this->isOnline($classTypeId);
        $room = $isOnline ? null : $this->availableRoom($data, $baseCapacity);

        if (! $isOnline && $room === null) {
            return null;
        }

        $teacher = $this->availableInstructor($course, $data);

        if ($teacher === null) {
            return null;
        }

        return StudyClass::create([
            'title' => $course->title,
            'course_id' => $course->id,
            'lesson_id' => $defaults?->lesson_id,
            'teacher_id' => $teacher->user_id,
            'room_id' => $room?->id,
            'class_type_id' => $classTypeId,
            'term_id' => $data['term_id'],
            'time_id' => $data['time_id'],
            'status' => 'upcoming',
            'capacity' => $room?->capacity ?: $baseCapacity,
            'price' => $course->enrollConfig?->price ?? $defaults?->price ?? 0,
            'document_price' => $course->enrollConfig?->document_price ?? $defaults?->document_price ?? 5,
            'enrollment_start_date' => now()->toDateString(),
            'start_date' => null,
            'end_date' => null,
        ]);
    }

    public function repairClass(StudyClass $studyClass): StudyClass
    {
        if (! $studyClass->course_id || ! $studyClass->term_id || ! $studyClass->time_id) {
            return $studyClass;
        }

        $studyClass->loadMissing('course.track.subCategory.category');

        if ($studyClass->course !== null) {
            $this->ensureClassAssignments($studyClass, $studyClass->course, [
                'term_id' => $studyClass->term_id,
                'time_id' => $studyClass->time_id,
            ]);
        }

        return $studyClass->refresh();
    }

    private function classTypeId(array $data, ?StudyClass $defaults = null): ?int
    {
        if ($this->hasScheduleFor($defaults?->class_type_id, $data)) {
            return $defaults->class_type_id;
        }

        $physicalId = ClassType::query()
            ->where('type_name', 'like', '%Physical%')
            ->orderBy('class_type_id')
            ->value('class_type_id');

        if ($this->hasScheduleFor($physicalId, $data)) {
            return (int) $physicalId;
        }

        return Schedule::query()
            ->where('term_id', $data['term_id'])
            ->whereHas('times', fn ($query) => $query->whereKey($data['time_id']))
            ->orderBy('class_type_id')
            ->value('class_type_id');
    }

    private function hasScheduleFor(?int $classTypeId, array $data): bool
    {
        return $classTypeId !== null
            && Schedule::query()
                ->where('class_type_id', $classTypeId)
                ->where('term_id', $data['term_id'])
                ->whereHas('times', fn ($query) => $query->whereKey($data['time_id']))
                ->exists();
    }

    private function availableRoom(array $data, int $capacity): ?Room
    {
        $roomIds = Room::query()
            ->where('status', 'available')
            ->where(function ($query) use ($capacity): void {
                $query->whereNull('capacity')
                    ->orWhere('capacity', '>=', $capacity);
            })
            ->orderByRaw('capacity IS NULL')
            ->orderBy('capacity')
            ->orderBy('id')
            ->pluck('id');

        foreach ($roomIds as $roomId) {
            $room = Room::query()->lockForUpdate()->find($roomId);

            if ($room !== null && ! $this->roomHasConflict($room->id, $data)) {
                return $room;
            }
        }

        return $this->fallbackRoom($data);
    }

    private function fallbackRoom(array $data): ?Room
    {
        $roomIds = Room::query()
            ->where('status', 'available')
            ->orderByDesc('capacity')
            ->orderBy('id')
            ->pluck('id');

        foreach ($roomIds as $roomId) {
            $room = Room::query()->lockForUpdate()->find($roomId);

            if ($room !== null && ! $this->roomHasConflict($room->id, $data)) {
                return $room;
            }
        }

        return null;
    }

    private function roomHasConflict(int $roomId, array $data): bool
    {
        return StudyClass::query()
            ->where('room_id', $roomId)
            ->where('term_id', $data['term_id'])
            ->where('time_id', $data['time_id'])
            ->whereIn('status', self::OPEN_CLASS_STATUSES)
            ->exists();
    }

    private function availableInstructor(Course $course, array $data): ?InstructorData
    {
        $days = $this->termDays((int) $data['term_id']);
        $range = $this->timeRange((int) $data['time_id']);

        $candidates = InstructorData::query()
            ->with(['availabilities' => fn ($query) => $query->where('is_active', true)])
            ->withCount(['availabilities as availability_slots' => fn ($query) => $query->where('is_active', true)])
            ->where('available_for_class', true)
            ->where('status', true)
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->orderByDesc('availability_slots')
            ->get();

        $available = $candidates
            ->filter(fn (InstructorData $instructor): bool => $days && $range['start'] !== null && $range['end'] !== null && $this->instructorCoversSchedule($instructor, $days, $range))
            ->filter(fn (InstructorData $instructor): bool => ! $this->instructorHasConflict($instructor, $data))
            ->values();

        if ($available->isEmpty()) {
            $available = $candidates
                ->filter(fn (InstructorData $instructor): bool => ! $this->instructorHasConflict($instructor, $data))
                ->values();
        }

        if ($available->isEmpty()) {
            return $this->fallbackInstructor($data);
        }

        $selected = $this->bestFieldMatch($available, $course) ?? $available->first();
        $selected = InstructorData::query()->lockForUpdate()->find($selected?->id);

        if ($selected === null || $this->instructorHasConflict($selected, $data)) {
            return null;
        }

        return $selected;
    }

    private function fallbackInstructor(array $data): ?InstructorData
    {
        $instructors = InstructorData::query()
            ->whereHas('user', fn ($query) => $query->where('status', 'active')->role('instructor'))
            ->orderBy('id')
            ->get();

        foreach ($instructors as $instructor) {
            $instructor = InstructorData::query()->lockForUpdate()->find($instructor->id);

            if ($instructor !== null && ! $this->instructorHasConflict($instructor, $data)) {
                return $instructor;
            }
        }

        return null;
    }

    private function instructorCoversSchedule(InstructorData $instructor, array $days, array $range): bool
    {
        foreach ($days as $day) {
            $covered = $instructor->availabilities->contains(function ($availability) use ($day, $range): bool {
                return (int) $availability->day_of_week === $day
                    && substr((string) $availability->start_time, 0, 5) <= $range['start']
                    && substr((string) $availability->end_time, 0, 5) >= $range['end'];
            });

            if (! $covered) {
                return false;
            }
        }

        return true;
    }

    private function instructorHasConflict(InstructorData $instructor, array $data): bool
    {
        return StudyClass::query()
            ->where('teacher_id', $instructor->user_id)
            ->where('term_id', $data['term_id'])
            ->where('time_id', $data['time_id'])
            ->whereIn('status', self::OPEN_CLASS_STATUSES)
            ->exists();
    }

    private function bestFieldMatch(Collection $instructors, Course $course): ?InstructorData
    {
        $keywords = collect([
            $course->title,
            $course->track?->name,
            $course->track?->subCategory?->name,
            $course->track?->subCategory?->category?->name,
        ])
            ->filter()
            ->map(fn (string $value): string => Str::lower($value))
            ->values();

        return $instructors->first(function (InstructorData $instructor) use ($keywords): bool {
            $specialization = Str::lower((string) $instructor->specialization);

            if ($specialization === '') {
                return false;
            }

            return $keywords->contains(fn (string $keyword): bool => str_contains($specialization, $keyword) || str_contains($keyword, $specialization));
        });
    }

    private function isOnline(?int $classTypeId): bool
    {
        return $classTypeId
            ? ClassType::query()->find($classTypeId)?->isOnline() ?? false
            : false;
    }

    private function termDays(int $termId): array
    {
        $termName = Term::query()->whereKey($termId)->value('term_name');
        $dayMap = [
            'Mon' => 1, 'Monday' => 1,
            'Tue' => 2, 'Tues' => 2, 'Tuesday' => 2,
            'Wed' => 3, 'Wednesday' => 3,
            'Thu' => 4, 'Thur' => 4, 'Thurs' => 4, 'Thursday' => 4,
            'Fri' => 5, 'Friday' => 5,
            'Sat' => 6, 'Saturday' => 6,
            'Sun' => 7, 'Sunday' => 7,
        ];

        return collect(preg_split('/\s*(?:-|,|&|\/|\+|and)\s*/i', (string) $termName))
            ->map(fn (string $day): ?int => $dayMap[trim($day)] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function timeRange(int $timeId): array
    {
        $timeName = Time::query()->whereKey($timeId)->value('time_name');
        $range = preg_match('/\(([^)]+)\)/', (string) $timeName, $match)
            ? $match[1]
            : $timeName;

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
