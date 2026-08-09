<?php

namespace Database\Seeders\Class;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Room;
use App\Models\StudyClass;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StudyClassSeeder extends Seeder
{
    /**
     * Seed 4 classes into the Enrollment Management feature (study_classes table).
     *
     * Depends on Core\CoreSeeder, Permission\UserSeeder, Course\CourseSeeder,
     * Building\BuildingSeeder, Class\ClassTypeSeeder and Schedule\ScheduleSeeder
     * having run so courses, instructors, rooms, class types, terms and times exist.
     */
    public function run(): void
    {
        $rooms = Room::query()->orderBy('id')->limit(2)->get();

        $instructors = User::role('instructor')->orderBy('id')->get();

        if ($instructors->isEmpty()) {
            throw new RuntimeException('StudyClassSeeder: no users with the instructor role found. Run UserSeeder first.');
        }

        $classes = [
            [
                'title' => 'Basic IT — Morning Batch',
                'course' => 'Basic IT',
                'lesson' => null,
                'teacher' => $instructors->get(0),
                'room' => $rooms->get(0),
                'class_type_name' => 'Physical Class',
                'status' => 'active',
                'term_name' => 'Mon & Thu',
                'time_name' => '09:00 am - 10:30 am',
                'capacity' => 20,
                'price' => 80,
                'document_price' => 5,
            ],
            [
                'title' => 'JavaScript + React.js — Weekend Batch',
                'course' => 'JavaScript + React.js, Domain Hosting',
                'lesson' => true,
                'teacher' => $instructors->get(1) ?? $instructors->get(0),
                'room' => null,
                'class_type_name' => 'Online Class',
                'status' => 'active',
                'term_name' => 'Sat & Sun',
                'time_name' => '08:00 am - 11:00 am',
                'capacity' => 25,
                'price' => 120,
                'document_price' => 5,
            ],
            [
                'title' => 'PHP / MySQL + Laravel — Evening Batch',
                'course' => 'PHP / MySQL + Laravel + Projects Web Backend (Basic/Advance)',
                'lesson' => null,
                'teacher' => $instructors->get(1) ?? $instructors->get(0),
                'room' => $rooms->get(1),
                'class_type_name' => 'Physical Class',
                'status' => 'upcoming',
                'term_name' => 'Wed & Thu',
                'time_name' => '02:00 pm - 3:15 pm',
                'capacity' => 18,
                'price' => 150,
                'document_price' => 5,
            ],
            [
                'title' => 'Dart + Flutter — Sunday Batch',
                'course' => 'Dart + Flutter (Basic/Advance - Java Required)',
                'lesson' => null,
                'teacher' => $instructors->get(0),
                'room' => null,
                'class_type_name' => 'Online Class',
                'status' => 'pre_end',
                'term_name' => 'Sunday',
                'time_name' => '07:15 pm - 8:30 pm',
                'capacity' => 15,
                'price' => 130,
                'document_price' => 5,
            ],
        ];

        foreach ($classes as $class) {
            $this->createClass($class);
        }
    }

    private function createClass(array $class): void
    {
        $today = CarbonImmutable::today();

        $payload = [
            'course_id' => $this->courseId($class['course']),
            'lesson_id' => $class['lesson'] === true
                ? $this->firstLessonId($this->courseId($class['course']))
                : null,
            'teacher_id' => $class['teacher']->id,
            'room_id' => $this->isOnline($class['class_type_name']) ? null : $class['room']?->id,
            'class_type_id' => $this->classTypeId($class['class_type_name']),
            'term_id' => $this->termId($class['term_name']),
            'time_id' => $this->timeId($class['time_name']),
            'status' => $class['status'],
            'capacity' => $class['capacity'],
            'price' => $class['price'],
            'document_price' => $class['document_price'],
            'enrollment_start_date' => $today->toDateString(),
            'start_date' => $today->addDays(7)->toDateString(),
            'end_date' => $today->addDays(90)->toDateString(),
        ];

        StudyClass::updateOrCreate(['title' => $class['title']], $payload);
    }

    private function isOnline(string $classTypeName): bool
    {
        return str_contains(strtolower($classTypeName), 'online');
    }

    private function courseId(string $title): int
    {
        $course = Course::query()->where('title', $title)->first();

        if (! $course) {
            throw new RuntimeException("StudyClassSeeder: course [{$title}] not found. Run CourseSeeder first.");
        }

        return $course->id;
    }

    private function firstLessonId(int $courseId): ?int
    {
        return CourseLesson::query()
            ->where('course_id', $courseId)
            ->orderBy('order_number')
            ->value('id');
    }

    private function classTypeId(string $typeName): int
    {
        return $this->lookup('class_type', 'class_type_id', 'type_name', 'class type', $typeName);
    }

    private function termId(string $termName): int
    {
        return $this->lookup('terms', 'id', 'term_name', 'term', $termName);
    }

    private function timeId(string $timeName): int
    {
        return $this->lookup('times', 'id', 'time_name', 'time', $timeName);
    }

    private function lookup(string $table, string $idColumn, string $nameColumn, string $label, string $value): int
    {
        $id = DB::table($table)->where($nameColumn, $value)->value($idColumn);

        if ($id === null) {
            throw new RuntimeException("StudyClassSeeder: {$label} [{$value}] not found. Run ScheduleSeeder first.");
        }

        return (int) $id;
    }
}
