<?php

namespace App\Modules\Enroll\Queries;

use App\Models\Building;
use App\Models\ClassType;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Floor;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;

class GetClassFormOptions
{
    public const CLASS_TYPES = ['physical', 'online'];
    public const STATUSES = ['upcoming', 'active', 'pre_end', 'ended', 'cancelled'];
    public const STUDY_DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public function handle(?StudyClass $studyClass = null): array
    {
        $buildingId = $studyClass?->room?->floor?->building_id;
        $floorId = $studyClass?->room?->floor_id;
        $courseId = $studyClass?->course_id;

        return [
            'courses' => Course::query()->select('id', 'title', 'price')->orderBy('title')->get(),
            'teachers' => User::query()->select('id', 'name')->orderBy('name')->get(),
            'buildings' => Building::query()->select('id', 'name')->orderBy('name')->get(),
            'floors' => $buildingId ? $this->floors($buildingId) : [],
            'rooms' => $floorId ? $this->rooms($floorId) : [],
            'lessons' => $courseId ? $this->lessons($courseId) : [],
            'terms' => $this->terms(),
            'times' => $this->times(),
            'classTypes' => $this->classTypes(),
            'statuses' => self::STATUSES,
            'studyDays' => self::STUDY_DAYS,
            'scheduleGroups' => $this->scheduleGroups(),
        ];
    }

    /**
     * Class type -> term -> time slots, as configured in Schedule Management,
     * so the class form can cascade Class Type -> Study Term -> Study Time.
     */
    public function scheduleGroups(): array
    {
        return Schedule::with(['classType', 'term', 'times'])
            ->get()
            ->sortBy(fn (Schedule $schedule) => $schedule->classType?->type_name ?? '')
            ->groupBy('class_type_id')
            ->map(function ($schedules) {
                $first = $schedules->first();

                return [
                    'class_type_id' => $first->class_type_id,
                    'class_type_name' => $first->classType?->type_name ?? '-',
                    'schedules' => $schedules->sortBy(fn (Schedule $schedule) => $schedule->term?->term_name ?? '')
                        ->values()
                        ->map(fn (Schedule $schedule) => [
                            'term_id' => $schedule->term_id,
                            'term_name' => $schedule->term?->term_name ?? '-',
                            'times' => $schedule->times->map(fn (Time $time) => [
                                'id' => $time->id,
                                'time_name' => $time->time_name,
                            ])->values(),
                        ]),
                ];
            })
            ->values()
            ->all();
    }

    public function floors(int $buildingId): array
    {
        return Floor::query()
            ->where('building_id', $buildingId)
            ->select('id', 'building_id', 'name', 'level')
            ->orderBy('level')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function rooms(int $floorId): array
    {
        return Room::query()
            ->where('floor_id', $floorId)
            ->select('id', 'floor_id', 'room_number', 'capacity')
            ->orderBy('room_number')
            ->get()
            ->all();
    }

    public function lessons(int $courseId): array
    {
        return CourseLesson::query()
            ->where('course_id', $courseId)
            ->select('id', 'course_id', 'title')
            ->orderBy('order_number')
            ->orderBy('title')
            ->get()
            ->all();
    }

    public function terms(): array
    {
        return Term::query()
            ->select('id', 'term_name')
            ->orderBy('term_name')
            ->get()
            ->all();
    }

    public function times(): array
    {
        return Time::query()
            ->select('id', 'time_name')
            ->orderBy('time_name')
            ->get()
            ->all();
    }

    public function classTypes(): array
    {
        $types = ClassType::query()
            ->where('is_active', true)
            ->select('class_type_id', 'type_name')
            ->orderBy('class_type_id')
            ->get()
            ->map(function (ClassType $type): ?array {
                $name = strtolower($type->type_name);

                if (str_contains($name, 'online')) {
                    return ['value' => 'online', 'label' => $type->type_name];
                }

                if (str_contains($name, 'physical')) {
                    return ['value' => 'physical', 'label' => $type->type_name];
                }

                return null;
            })
            ->filter()
            ->unique('value')
            ->values()
            ->all();

        return $types ?: [
            ['value' => 'physical', 'label' => 'Physical Class'],
            ['value' => 'online', 'label' => 'Online Class'],
        ];
    }
}
