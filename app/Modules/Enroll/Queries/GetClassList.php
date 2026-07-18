<?php

namespace App\Modules\Enroll\Queries;

use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GetClassList
{
    public function handle(Request $request): array
    {
        $search = trim($request->string('search')->toString());

        $classes = StudyClass::query()
            ->select([
                'id',
                'title',
                'course_id',
                'lesson_id',
                'teacher_id',
                'room_id',
                'class_type',
                'status',
                'study_days',
                'start_time',
                'end_time',
                'capacity',
                'price',
                'enrollment_start_date',
                'start_date',
                'end_date',
            ])
            ->with([
                'course:id,title',
                'lesson:id,course_id,title',
                'teacher:id,name',
                'room:id,floor_id,room_number',
                'room.floor:id,building_id,name,level',
                'room.floor.building:id,name',
            ])
            ->withCount([
                'enrollments as current_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
            ])
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return [
            'classes' => $classes->through(fn (StudyClass $studyClass) => $this->presentClass($studyClass)),
            'depositSummary' => $this->summary(),
            'filters' => [
                'search' => $search,
            ],
        ];
    }

    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search): void {
            $query->where('title', 'like', "%{$search}%")
                ->orWhereHas('course', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
                ->orWhereHas('lesson', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
                ->orWhereHas('teacher', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                ->orWhereHas('room', fn (Builder $query) => $query->where('room_number', 'like', "%{$search}%"))
                ->orWhereHas('room.floor.building', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
        });
    }

    public function presentClass(StudyClass $studyClass): array
    {
        $currentStudents = (int) ($studyClass->current_students ?? $studyClass->enrollments_count ?? 0);
        $capacity = (int) $studyClass->capacity;
        $availableSeats = max($capacity - $currentStudents, 0);
        $filledPercentage = $capacity > 0 ? round(($currentStudents / $capacity) * 100, 2) : 0;
        $studyDays = $studyClass->study_days ?? [];

        return [
            'id' => $studyClass->id,
            'title' => $studyClass->title,
            'course' => $studyClass->course?->title,
            'lesson' => $studyClass->lesson?->title ?? '-',
            'teacher' => $studyClass->teacher?->name ?? '-',
            'building' => $studyClass->room?->floor?->building?->name ?? '-',
            'floor' => $studyClass->room?->floor?->name ?? '-',
            'room' => $studyClass->room?->room_number ?? ($studyClass->class_type === 'online' ? 'Online' : '-'),
            'class_type' => $studyClass->class_type,
            'status' => $studyClass->class_type === 'online' ? 'Online Class' : 'Physical Class',
            'class_status' => $studyClass->status,
            'study_days' => $studyDays,
            'term' => implode(' & ', $studyDays),
            'start_time' => $this->formatTime($studyClass->start_time),
            'end_time' => $this->formatTime($studyClass->end_time),
            'time' => $this->formatTime($studyClass->start_time).' - '.$this->formatTime($studyClass->end_time),
            'capacity' => $capacity,
            'price' => (float) $studyClass->price,
            'students' => $currentStudents,
            'current_students' => $currentStudents,
            'available_seats' => $availableSeats,
            'filled_percentage' => $filledPercentage,
            'notifications' => 0,
        ];
    }

    private function summary(): array
    {
        $active = StudentEnrollment::query()->where('enrollment_status', 'active');

        return [
            'total_students' => (clone $active)->count(),
            'paid_students' => (clone $active)->where('payment_status', 'paid')->count(),
            'partial_students' => (clone $active)->where('payment_status', 'partial')->count(),
            'unpaid_students' => (clone $active)->whereIn('payment_status', ['unpaid', 'partial'])->count(),
            'total_deposit_collected' => (float) (clone $active)->sum('amount_paid'),
        ];
    }

    private function formatTime(?string $time): string
    {
        return $time ? substr($time, 0, 5) : '-';
    }
}
