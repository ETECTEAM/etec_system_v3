<?php

namespace App\Modules\Instructor\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class InstructorClassService
{
    private ?array $termLabels = null;

    public function formOptions(): array
    {
        return [
            'courses' => DB::table('courses')->select('id', 'title')->get(),
            'lessons' => DB::table('course_lessons')->select('id', 'title')->get(),
            'terms' => DB::table('terms')->select('id', 'term_name')->get(),
            'times' => DB::table('times')->select('id', 'time_name')->get(),
            'rooms' => DB::table('rooms')->select('id', 'room_number')->get(),
            'classTypes' => DB::table('class_type')->select('class_type_id', 'type_name')->get(),
        ];
    }

    public function createClass(User $instructor, array $data): int
    {
        $now = now();

        return DB::table('study_classes')->insertGetId([
            'title' => $data['title'],
            'course_id' => $data['course_id'],
            'lesson_id' => $data['lesson_id'] ?? null,
            'term_id' => $data['term_id'] ?? null,
            'time_id' => $data['time_id'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'class_type_id' => $data['class_type_id'] ?? null,
            'capacity' => $data['capacity'] ?? 20,
            'status' => $data['status'] ?? 'upcoming',
            'teacher_id' => $instructor->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function classes(User $instructor): Collection
    {
        return $this->classesQuery()
            ->where('study_classes.teacher_id', $instructor->id)
            ->orderByDesc('study_classes.id')
            ->get()
            ->map(fn (stdClass $class) => $this->presentClass($class));
    }

    public function summary(User $instructor): array
    {
        $activeEnrollments = DB::table('student_enrollments')
            ->join('study_classes', 'study_classes.id', '=', 'student_enrollments.study_class_id')
            ->where('student_enrollments.enrollment_status', 'active')
            ->where('study_classes.teacher_id', $instructor->id);

        return [
            'total_classes' => DB::table('study_classes')->where('teacher_id', $instructor->id)->count(),
            'total_students' => (clone $activeEnrollments)->count(),
            'male_students' => (clone $activeEnrollments)
                ->join('students', 'students.user_id', '=', 'student_enrollments.student_id')
                ->where('students.gender', 'male')
                ->count(),
            'female_students' => (clone $activeEnrollments)
                ->join('students', 'students.user_id', '=', 'student_enrollments.student_id')
                ->where('students.gender', 'female')
                ->count(),
        ];
    }

    public function findForInstructor(User $instructor, int $studyClassId): stdClass
    {
        $class = $this->classesQuery()
            ->where('study_classes.id', $studyClassId)
            ->where('study_classes.teacher_id', $instructor->id)
            ->first();

        abort_unless($class, 403);

        return $class;
    }

    public function students(int $studyClassId): Collection
    {
        return DB::table('student_enrollments')
            ->join('users as students_user', 'students_user.id', '=', 'student_enrollments.student_id')
            ->leftJoin('students', 'students.user_id', '=', 'students_user.id')
            ->where('student_enrollments.study_class_id', $studyClassId)
            ->where('student_enrollments.enrollment_status', 'active')
            ->orderBy('student_enrollments.id')
            ->select([
                'student_enrollments.id as enrollment_id',
                'students_user.id',
                'students_user.name',
                'students_user.email',
                'students.gender',
                'students.phone',
            ])
            ->get()
            ->map(fn (stdClass $student, int $index) => $this->presentStudent($student, $index + 1));
    }

    public function presentClass(stdClass $class): array
    {
        $studyDays = $this->parseTermDays($class->term_name);
        $timeRange = $this->parseTimeRange($class->time_name);
        $classTypeValue = $this->classTypeValue($class->class_type_name);
        $classTypeLabel = $class->class_type_name
            ?? ($classTypeValue === 'online' ? 'Online Class' : 'Physical Class');

        return [
            'id' => $class->id,
            'title' => $class->title,
            'course' => $class->course_title,
            'lesson' => $class->lesson_title ?? 'No lesson',
            'teacher' => $class->teacher_name ?? '-',
            'building' => $class->building_name ?? '-',
            'floor' => $class->floor_name ?? '-',
            'room' => $class->room_number ?? ($classTypeValue === 'online' ? 'Online' : '-'),
            'status' => $classTypeLabel,
            'class_status' => $class->class_status,
            'term' => $this->termLabel($studyDays),
            'time' => ($timeRange['start'] ?? '-').' - '.($timeRange['end'] ?? '-'),
            'capacity' => (int) $class->capacity,
            'students' => (int) $class->current_students,
            'created_date' => $class->created_at ? Carbon::parse($class->created_at)->format('Y-m-d H:i:s') : null,
        ];
    }

    private function classesQuery()
    {
        $activeStudentCounts = DB::table('student_enrollments')
            ->select('study_class_id', DB::raw('count(*) as current_students'))
            ->where('enrollment_status', 'active')
            ->groupBy('study_class_id');

        return DB::table('study_classes')
            ->leftJoin('courses', 'courses.id', '=', 'study_classes.course_id')
            ->leftJoin('course_lessons', 'course_lessons.id', '=', 'study_classes.lesson_id')
            ->leftJoin('users as teachers', 'teachers.id', '=', 'study_classes.teacher_id')
            ->leftJoin('rooms', 'rooms.id', '=', 'study_classes.room_id')
            ->leftJoin('floors', 'floors.id', '=', 'rooms.floor_id')
            ->leftJoin('buildings', 'buildings.id', '=', 'floors.building_id')
            ->leftJoin('class_type', 'class_type.class_type_id', '=', 'study_classes.class_type_id')
            ->leftJoin('terms', 'terms.id', '=', 'study_classes.term_id')
            ->leftJoin('times', 'times.id', '=', 'study_classes.time_id')
            ->leftJoinSub($activeStudentCounts, 'active_student_counts', function ($join) {
                $join->on('active_student_counts.study_class_id', '=', 'study_classes.id');
            })
            ->select([
                'study_classes.id',
                'study_classes.title',
                'study_classes.capacity',
                'study_classes.status as class_status',
                'study_classes.created_at',
                'courses.title as course_title',
                'course_lessons.title as lesson_title',
                'teachers.name as teacher_name',
                'rooms.room_number',
                'floors.name as floor_name',
                'buildings.name as building_name',
                'class_type.type_name as class_type_name',
                'terms.term_name',
                'times.time_name',
                DB::raw('coalesce(active_student_counts.current_students, 0) as current_students'),
            ]);
    }

    private function presentStudent(stdClass $student, int $rosterNo): array
    {
        return [
            'id' => $student->id,
            'roster_no' => $rosterNo,
            'enrollment_id' => $student->enrollment_id,
            'name' => $student->name ?? '-',
            'email' => $student->email,
            'gender' => $student->gender ?? '-',
            'phone' => $student->phone ?? '-',
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

    private function classTypeValue(?string $classTypeName): string
    {
        return str_contains(strtolower((string) $classTypeName), 'online') ? 'online' : 'physical';
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

        return $this->termLabels = DB::table('terms')
            ->select('term_name')
            ->orderBy('term_name')
            ->get()
            ->mapWithKeys(fn (stdClass $term) => [
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

    private function parseTimeRange(?string $timeName): array
    {
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
