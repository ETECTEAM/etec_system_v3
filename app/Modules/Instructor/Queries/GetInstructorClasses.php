<?php

namespace App\Modules\Instructor\Queries;

use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetInstructorClasses
{
    private ?array $termLabels = null;

    public function classes(User $instructor): Collection
    {
        return StudyClass::query()
            ->select([
                'id',
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
                'start_date',
                'end_date',
                'created_at',
            ])
            ->where('teacher_id', $instructor->id)
            ->with($this->relations())
            ->withCount([
                'enrollments as current_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
            ])
            ->latest('id')
            ->get()
            ->map(fn (StudyClass $studyClass) => $this->presentClass($studyClass));
    }

    public function summary(User $instructor): array
    {
        $activeEnrollments = StudentEnrollment::query()
            ->where('enrollment_status', 'active')
            ->whereHas('studyClass', fn (Builder $query) => $query->where('teacher_id', $instructor->id));

        return [
            'total_classes' => StudyClass::query()->where('teacher_id', $instructor->id)->count(),
            'total_students' => (clone $activeEnrollments)->count(),
            'male_students' => (clone $activeEnrollments)
                ->whereHas('student.student', fn (Builder $query) => $query->where('gender', 'male'))
                ->count(),
            'female_students' => (clone $activeEnrollments)
                ->whereHas('student.student', fn (Builder $query) => $query->where('gender', 'female'))
                ->count(),
        ];
    }

    public function findForInstructor(User $instructor, StudyClass $studyClass): StudyClass
    {
        abort_unless((int) $studyClass->teacher_id === (int) $instructor->id, 403);

        return $studyClass
            ->load($this->relations())
            ->loadCount([
                'enrollments as current_students' => fn (Builder $query) => $query->where('enrollment_status', 'active'),
            ]);
    }

    public function students(StudyClass $studyClass): Collection
    {
        return StudentEnrollment::query()
            ->where('study_class_id', $studyClass->id)
            ->where('enrollment_status', 'active')
            ->with(['student:id,name,email', 'student.student:id,user_id,gender,phone'])
            ->orderBy('id')
            ->get()
            ->map(fn (StudentEnrollment $enrollment, int $index) => $this->presentStudent($enrollment, $index + 1));
    }

    public function presentClass(StudyClass $studyClass): array
    {
        $currentStudents = (int) ($studyClass->current_students ?? $studyClass->enrollments_count ?? 0);
        $capacity = (int) $studyClass->capacity;
        $studyDays = $studyClass->scheduleStudyDays();
        $classTypeValue = $studyClass->classTypeValue();
        $classTypeLabel = $studyClass->classType?->type_name
            ?? ($classTypeValue === 'online' ? 'Online Class' : 'Physical Class');

        return [
            'id' => $studyClass->id,
            'title' => $studyClass->title,
            'course' => $studyClass->course?->title,
            'lesson' => $studyClass->lesson?->title ?? 'No lesson',
            'teacher' => $studyClass->teacher?->name ?? '-',
            'building' => $studyClass->room?->floor?->building?->name ?? '-',
            'floor' => $studyClass->room?->floor?->name ?? '-',
            'room' => $studyClass->room?->room_number ?? ($studyClass->isOnline() ? 'Online' : '-'),
            'status' => $classTypeLabel,
            'class_status' => $studyClass->status,
            'term' => $this->termLabel($studyDays),
            'time' => $this->formatTime($studyClass->scheduleStartTime()).' - '.$this->formatTime($studyClass->scheduleEndTime()),
            'capacity' => $capacity,
            'students' => $currentStudents,
            'created_date' => optional($studyClass->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function presentStudent(StudentEnrollment $enrollment, int $rosterNo): array
    {
        $student = $enrollment->student;
        $profile = $student?->student;

        return [
            'id' => $student?->id,
            'roster_no' => $rosterNo,
            'enrollment_id' => $enrollment->id,
            'name' => $student?->name ?? '-',
            'email' => $student?->email,
            'gender' => $profile?->gender ?? '-',
            'phone' => $profile?->phone ?? '-',
            'attendance' => [
                'total' => 0,
                'present' => 0,
                'permission' => 0,
                'absent' => 0,
            ],
            'scores' => [
                'attendance' => 0,
                'activity' => 0,
                'exam' => 0,
            ],
        ];
    }

    private function relations(): array
    {
        return [
            'course:id,title',
            'lesson:id,course_id,title',
            'teacher:id,name',
            'room:id,floor_id,room_number',
            'room.floor:id,building_id,name,level',
            'room.floor.building:id,name',
            'classType:class_type_id,type_name',
            'term:id,term_name',
            'time:id,time_name',
        ];
    }

    private function formatTime(?string $time): string
    {
        return $time ? substr($time, 0, 5) : '-';
    }

    private function termLabel(array $studyDays): string
    {
        if (! $studyDays) {
            return '-';
        }

        $key = $this->studyDaysKey($studyDays);

        return $this->termLabels()[$key] ?? implode(' & ', $studyDays);
    }

    private function termLabels(): array
    {
        if ($this->termLabels !== null) {
            return $this->termLabels;
        }

        return $this->termLabels = Term::query()
            ->select('term_name')
            ->orderBy('term_name')
            ->get()
            ->mapWithKeys(fn (Term $term) => [
                $this->studyDaysKey($this->parseTermDays($term->term_name)) => $term->term_name,
            ])
            ->filter(fn (string $label, string $key) => $key !== '')
            ->all();
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

    private function studyDaysKey(array $studyDays): string
    {
        return collect($studyDays)
            ->map(fn (string $day) => strtolower(trim($day)))
            ->sort()
            ->implode('|');
    }
}
