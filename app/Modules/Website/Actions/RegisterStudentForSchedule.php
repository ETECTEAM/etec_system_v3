<?php

namespace App\Modules\Website\Actions;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Models\Notification;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Modules\Enroll\Services\InstructorAssignmentAvailability;
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

    public function __construct(
        private readonly StudentRegistrationService $registrations,
        private readonly InstructorAssignmentAvailability $instructorAvailability,
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

            $studyClass = $this->availableClass($course, $data);
            $noRoom = false;
            $noInstructor = false;
            $price = null;
            $documentPrice = null;

            if ($studyClass === null) {
                $created = $this->createClass($course, $data);
                $studyClass = $created['class'];
                $noRoom = $created['no_room'];
                $noInstructor = $created['no_instructor'];
                $price = $created['price'];
                $documentPrice = $created['document_price'];
            }

            if ($studyClass === null) {
                $this->saveUnassignedEnrollment($student, $course, $data, $noRoom, $noInstructor, $price, $documentPrice);

                return null;
            }

            // Re-resolves the current CourseEnrollConfig rather than trusting
            // $studyClass->price: that column is only ever set once, when the
            // class itself was first created (see createClass() below), so an
            // admin changing "Price to Use" afterward would otherwise never
            // reach a student registering into that same pre-existing class.
            $config = $course->enrollConfigForTime(isset($data['time_id']) ? (int) $data['time_id'] : null);

            $enrollment = StudentEnrollment::create([
                'study_class_id' => $studyClass->id,
                'student_id' => $student->id,
                'source' => 'public_website',
                'fee_amount' => $config?->resolvedPrice() ?? $studyClass->price,
                'document_fee_amount' => $config?->document_price ?? $studyClass->document_price,
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

        NotificationsUpdated::dispatch();

        return $enrollment;
    }

    // No open class had space, and creating a new one wasn't possible (no
    // free room and/or no free instructor for that term/time), so the
    // registration is parked as a classless StudentEnrollment instead of
    // silently creating a roomless/teacherless class. It still carries a real
    // fee/document fee (the same price createClass() would have charged) so
    // staff can record payment right away, before a class is assigned. A
    // super_admin/admin resolves it from the Registrations tab
    // (MoveStudentEnrollment assigns a classless enrollment in place - see
    // its null study_class_id branch).
    private function saveUnassignedEnrollment(stdClass $student, Course $course, array $data, bool $noRoom, bool $noInstructor, float $price, float $documentPrice): void
    {
        StudentEnrollment::create([
            'study_class_id' => null,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'term_id' => $data['term_id'],
            'time_id' => $data['time_id'],
            'enrollment_status' => 'unassigned',
            'source' => 'public_website',
            'fee_amount' => $price,
            'document_fee_amount' => $documentPrice,
            'enrolled_at' => now(),
            'no_room_and_instructor' => $noRoom && $noInstructor,
            'no_room' => $noRoom && ! $noInstructor,
            'no_instructor' => $noInstructor && ! $noRoom,
        ]);

        // dashboard_notifications has no per-recipient addressing; the bell
        // and notifications page are already gated to super_admin/admin in
        // the UI (DashboardHeader.vue), which is the targeting used here.
        Notification::create([
            'title' => 'Registration needs manual scheduling',
            'message' => "{$data['name']} wants to join \"{$course->title}\" but no room or instructor is available for that term and time."
                .' Assign them to a class from the Registrations tab.',
            'type' => 'unassigned_registration',
        ]);
    }

    private function availableClass(Course $course, array $data): ?StudyClass
    {
        // class_type_id disambiguates term+time pairs shared by more than
        // one class type's schedule.
        $classes = StudyClass::query()
            ->where('course_id', $course->id)
            ->where('term_id', $data['term_id'])
            ->where('time_id', $data['time_id'])
            ->when(
                isset($data['class_type_id']),
                fn ($query) => $query->where('class_type_id', (int) $data['class_type_id']),
            )
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

    // Doesn't create a roomless/teacherless class when a physical room is
    // required but none is free, or no instructor is free, for this
    // term/time - instead reports which resource(s) were missing so the
    // caller can flag the parked enrollment accordingly. Both checks always
    // run (no early return) so a "both missing" case is reported precisely,
    // not just whichever was checked first.
    //
    // @return array{class: ?StudyClass, no_room: bool, no_instructor: bool, price: float, document_price: float}
    private function createClass(Course $course, array $data): array
    {
        $course->loadMissing('track.subCategory.category', 'enrollConfigs.time');

        $defaults = StudyClass::query()
            ->where('course_id', $course->id)
            ->latest('id')
            ->first();

        $config = $course->enrollConfigForTime(isset($data['time_id']) ? (int) $data['time_id'] : null);
        $price = $config?->resolvedPrice() ?? $defaults?->price ?? 0;
        $documentPrice = $config?->document_price ?? $defaults?->document_price ?? 5;

        $classTypeId = $this->classTypeId($data, $defaults) ?? $defaults?->class_type_id;
        $baseCapacity = $defaults?->capacity ?: self::DEFAULT_CAPACITY;
        $isOnline = $this->isOnline($classTypeId);
        $room = $isOnline ? null : $this->availableRoom($data, $baseCapacity);
        $noRoom = ! $isOnline && $room === null;

        $teacher = $this->availableInstructor($course, $data);
        $noInstructor = $teacher === null;

        if ($noRoom || $noInstructor) {
            return ['class' => null, 'no_room' => $noRoom, 'no_instructor' => $noInstructor, 'price' => $price, 'document_price' => $documentPrice];
        }

        $studyClass = StudyClass::create([
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
            'price' => $price,
            'document_price' => $documentPrice,
            'enrollment_start_date' => now()->toDateString(),
            'start_date' => null,
            'end_date' => null,
        ]);

        return ['class' => $studyClass, 'no_room' => false, 'no_instructor' => false, 'price' => $price, 'document_price' => $documentPrice];
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
        // Explicit choice (from the public form) wins over the heuristics below.
        if (isset($data['class_type_id']) && $this->hasScheduleFor((int) $data['class_type_id'], $data)) {
            return (int) $data['class_type_id'];
        }

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
        $dayNumbers = [
            'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4,
            'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7,
        ];

        return collect(StudyClass::parseTermDays($termName))
            ->map(fn (string $day): ?int => $dayNumbers[$day] ?? null)
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
