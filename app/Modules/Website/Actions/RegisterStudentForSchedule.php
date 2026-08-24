<?php

namespace App\Modules\Website\Actions;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Models\Notification;
use App\Models\PendingRegistration;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;
use App\Modules\Enroll\Services\StudentRegistrationService;
use App\Modules\Notification\Events\NotificationsUpdated;
use App\Services\TelegramNotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use stdClass;

class RegisterStudentForSchedule
{
    private const DEFAULT_CAPACITY = 12;
    private const OPEN_CLASS_STATUSES = ['upcoming', 'active', 'pre_end'];

    public function __construct(
        private readonly StudentRegistrationService $registrations,
        private readonly InstructorAssignmentAvailability $instructorAvailability,
        private readonly TelegramNotificationService $telegram,
    ) {}

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

            DB::table('dashboard_notifications')->insert([
                'title' => 'New Student Registration',
                'message' => "{$data['name']} registered for \"{$studyClass->title}\".",
                'type' => 'class_registration',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $enrollment;
        });

        // Sent only once the transaction has committed (and outside it), so a
        // Telegram hiccup can neither roll back the registration nor run
        // inside a DB transaction that might retry. The service itself swallows
        // failures - see TelegramNotificationService.
        $this->telegram->send($this->registrationAlertMessage($data, $enrollment));

        NotificationsUpdated::dispatch();

        return $enrollment;
    }

    private function registrationAlertMessage(array $data, ?StudentEnrollment $enrollment): string
    {
        $course = Course::query()->find($data['course_id']);

        if ($enrollment === null) {
            return "🚨 <b>New Registration Needs Manual Scheduling</b>\n"
                ."\n👤 Student: {$data['name']}"
                ."\n📞 Phone: {$data['phone']}"
                ."\n📚 Course: {$course?->title}"
                ."\n⏳ Status: Pending - no room or instructor available; an admin must assign a class manually.";
        }

        $studyClass = StudyClass::query()->find($enrollment->study_class_id);

        return "🚨 <b>New Online Registration</b>\n"
            ."\n👤 Student: {$data['name']}"
            ."\n📞 Phone: {$data['phone']}"
            ."\n📚 Course: {$course?->title}"
            ."\n🎓 Class: {$studyClass?->title}";
    }

    // No open class had space, and creating a new one wasn't possible (no
    // free room and/or no free instructor for that term/time), so the
    // student/course/term/time is parked here instead of silently creating a
    // roomless/teacherless class. Candidate classes an admin may force-assign
    // the student into (2-week rule, see findEligibleClassesForAdmin) are
    // snapshotted into meta, and a notification with the pending/student/
    // candidate class IDs is raised for super_admins/admins, who resolve it
    // via AssignPendingStudentToClass.
    private function savePendingRegistration(stdClass $student, Course $course, array $data): void
    {
        $candidateClassIds = $this->findEligibleClassesForAdmin($course);

        $pending = PendingRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'term_id' => $data['term_id'],
            'time_id' => $data['time_id'],
            'status' => 'pending',
            'meta' => ['candidate_class_ids' => $candidateClassIds],
        ]);

        // dashboard_notifications has no per-recipient addressing; the bell
        // and notifications page are already gated to super_admin/admin in
        // the UI (DashboardHeader.vue), which is the targeting used here.
        Notification::create([
            'title' => 'Registration needs manual scheduling',
            'message' => "{$data['name']} wants to join \"{$course->title}\" but no room or instructor is available for that term and time."
                ." Pending registration #{$pending->id}, student #{$student->id}."
                .' Classes eligible for assignment (created or starting within 2 weeks): '
                .($candidateClassIds === [] ? 'none' : implode(', ', $candidateClassIds))
                .'. Assign them to a class manually.',
            'type' => 'pending_registration',
        ]);
    }

    // Open classes for this course an admin may force-assign a parked student
    // into. A class qualifies when it satisfies AT LEAST one of:
    //   A) created within the last 2 weeks, or
    //   B) start_date within [now - 2 weeks, now + 2 weeks]
    // (start_date is often null for freshly created upcoming classes, which is
    // why condition B only applies when it is set). Newest first so admins see
    // the most recently created options first.
    private function findEligibleClassesForAdmin(Course $course): array
    {
        return StudyClass::query()
            ->where('course_id', $course->id)
            ->whereIn('status', self::OPEN_CLASS_STATUSES)
            ->where(function ($query): void {
                $query->where('created_at', '>=', now()->subWeeks(2)->toDateTimeString())
                    ->orWhere(function ($query): void {
                        $query->whereNotNull('start_date')
                            ->whereBetween('start_date', [
                                now()->subWeeks(2)->toDateString(),
                                now()->addWeeks(2)->toDateString(),
                            ]);
                    });
            })
            ->orderByDesc('id')
            ->pluck('id')
            ->all();
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
        $course->loadMissing('track.subCategory.category', 'enrollConfigs.time');

        $defaults = StudyClass::query()
            ->where('course_id', $course->id)
            ->latest('id')
            ->first();

        $config = $course->enrollConfigForTime(isset($data['time_id']) ? (int) $data['time_id'] : null);

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
            'price' => $config?->resolvedPrice() ?? $defaults?->price ?? 0,
            'document_price' => $config?->document_price ?? $defaults?->document_price ?? 5,
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
            ->filter(fn (InstructorData $instructor): bool => ! $this->instructorHasManualBlock($instructor, $data))
            ->values();

        if ($available->isEmpty()) {
            return null;
        }

        $selected = $this->bestFieldMatch($available, $course) ?? $available->first();
        $selected = InstructorData::query()->lockForUpdate()->find($selected?->id);

        if ($selected === null || $this->instructorHasConflict($selected, $data) || $this->instructorHasManualBlock($selected, $data)) {
            return null;
        }

        return $selected;
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
        // Weekday-aware and includes shared (study_class_instructors) classes, so
        // an instructor already teaching another class on an overlapping day at the
        // same time is excluded even when the two terms only partially overlap.
        return $this->instructorAvailability->hasConflictingClass(
            $instructor->user_id,
            $this->termDays((int) $data['term_id']),
            (int) $data['time_id'],
        );
    }

    // A staff-entered exception (see InstructorScheduleBlockController), separate
    // from ShiftTemplate/InstructorAvailability - never modifies those, just adds
    // another exclusion checked alongside the conflict check above at every call
    // site. $data['term_id'] is expanded to its weekday set the same way
    // instructorCoversSchedule() does, since a block is stored per calendar day,
    // not per term.
    private function instructorHasManualBlock(InstructorData $instructor, array $data): bool
    {
        $days = $this->termDays((int) $data['term_id']);

        if ($days === [] || ! isset($data['time_id'])) {
            return false;
        }

        return InstructorScheduleBlock::query()
            ->where('instructor_id', $instructor->id)
            ->where('time_id', $data['time_id'])
            ->whereIn('day_of_week', $days)
            ->where('status', InstructorScheduleBlock::STATUS_ACTIVE)
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
            $specializations = collect($instructor->specialization ?? [])
                ->map(fn (string $value): string => Str::lower($value))
                ->filter(fn (string $value): bool => $value !== '');

            return $specializations->contains(
                fn (string $specialization): bool => $keywords->contains(
                    fn (string $keyword): bool => str_contains($specialization, $keyword) || str_contains($keyword, $specialization)
                )
            );
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
