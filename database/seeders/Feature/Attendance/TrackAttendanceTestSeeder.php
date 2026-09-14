<?php

namespace Database\Seeders\Feature\Attendance;

use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\Course;
use App\Models\Room;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * One active "Basic IT" class owned by the first instructor, with 12 enrolled
 * students and today's ClassSession already in `pre_attendance` - so the
 * instructor can open Track Attendance and record all 12 right away, no matter
 * the wall clock, and the class is also ready to exercise "Collapse Class"
 * (multi-day term, a second available instructor exists via
 * InstructorWorkScheduleSeeder).
 *
 * Idempotent: re-running wipes its own data first (class titled
 * "[TrackAtt Test] ...", students with phone 097812xx). Not part of db:seed.
 *
 * Run:
 *   php artisan db:seed --class="Database\Seeders\Feature\Attendance\TrackAttendanceTestSeeder"
 */
class TrackAttendanceTestSeeder extends Seeder
{
    // The class is titled with the plain course name; its own data is found
    // for the idempotent wipe via this student-phone marker instead.
    private const STUDENT_PHONE_PREFIX = '097812';

    private const STUDENT_COUNT = 12;

    public function run(): void
    {
        $instructor = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'instructor'))
            ->orderBy('id')
            ->first();

        if (! $instructor) {
            $this->command?->error('No user with the "instructor" role found - run the base seeders first.');

            return;
        }

        $refs = $this->resolveReferences();
        $this->wipePrevious();

        $today = Carbon::today('Asia/Phnom_Penh');
        $now = Carbon::now('Asia/Phnom_Penh');

        $classId = DB::table('study_classes')->insertGetId([
            'title' => $refs['course_title'],
            'course_id' => $refs['course_id'],
            'lesson_id' => null,
            'teacher_id' => $instructor->id,
            'room_id' => $refs['room_id'],
            'class_type_id' => $refs['class_type_id'],
            'term_id' => $refs['term_id'],
            'time_id' => $refs['time_id'],
            'status' => 'active',
            'capacity' => 20,
            'start_date' => $today->copy()->subWeeks(3)->toDateString(),
            'end_date' => $today->copy()->addMonths(2)->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        for ($i = 1; $i <= self::STUDENT_COUNT; $i++) {
            $studentId = DB::table('students')->insertGetId([
                'user_id' => null,
                'full_name' => sprintf('TrackAtt Student %02d', $i),
                'gender' => $i % 2 === 0 ? 'female' : 'male',
                'phone' => self::STUDENT_PHONE_PREFIX.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'student_status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('student_enrollments')->insert([
                'study_class_id' => $classId,
                'student_id' => $studentId,
                'enrollment_status' => 'active',
                'payment_status' => 'paid',
                'fee_amount' => 0,
                'amount_paid' => 0,
                'enrolled_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('class_sessions')->insert([
            'study_class_id' => $classId,
            'instructor_id' => $instructor->id,
            'session_date' => $today->toDateString(),
            'scheduled_start' => $today->copy()->setTime(11, 0)->toDateTimeString(),
            'scheduled_end' => $today->copy()->setTime(12, 15)->toDateTimeString(),
            'status' => ClassSession::STATUS_PRE_ATTENDANCE,
            'recorded_at' => null,
            'grace_minutes_used' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->command?->info('');
        $this->command?->info(sprintf('Seeded class #%d "%s" for instructor "%s" (id %d) with %d students.', $classId, $refs['course_title'], $instructor->name, $instructor->id, self::STUDENT_COUNT));
        $this->command?->info('Track Attendance: log in as that instructor -> My Classes -> this class -> "Track Attendance" (session is pre_attendance, so all 12 are submittable now).');
        $this->command?->info('Collapse Class: as admin, open this class in Class List -> "Collapse Class", share with instructor2@etec.com, give the shared half a term such as "Wed & Thu".');
    }

    /**
     * @return array{course_id:int,course_title:string,room_id:?int,class_type_id:?int,term_id:int,time_id:int}
     */
    private function resolveReferences(): array
    {
        $course = Course::query()->where('title', 'Basic IT')->first()
            ?? Course::query()->firstOrCreate(
                ['title' => 'Basic IT'],
                ['slug' => 'basic-it', 'status' => 'active'],
            );

        // A multi-day weekday term + a mid-morning slot: covered by the
        // "morning" work schedules, so a second instructor is always available
        // for the Collapse Class dialog.
        $term = Term::query()->where('term_name', 'Mon & Thu')->first()
            ?? Term::query()->first()
            ?? Term::query()->create(['term_name' => 'Mon & Thu']);

        $time = Time::query()->where('time_name', 'like', '11:00%12:15%')->first()
            ?? Time::query()->first()
            ?? Time::query()->create(['time_name' => '11:00 am - 12:15 pm']);

        return [
            'course_id' => $course->id,
            'course_title' => $course->title,
            'room_id' => Room::query()->value('id'),
            'class_type_id' => ClassType::query()->where('type_name', 'Physical Class')->value('class_type_id')
                ?? ClassType::query()->value('class_type_id'),
            'term_id' => $term->id,
            'time_id' => $time->id,
        ];
    }

    private function wipePrevious(): void
    {
        // The class title is just "Basic IT" now, so find this seeder's own
        // class through the students it enrolled. Deleting the class cascades
        // to student_enrollments, class_sessions and student_attendances.
        $studentIds = DB::table('students')
            ->where('phone', 'like', self::STUDENT_PHONE_PREFIX.'%')
            ->pluck('id');

        if ($studentIds->isEmpty()) {
            return;
        }

        $classIds = DB::table('student_enrollments')
            ->whereIn('student_id', $studentIds)
            ->pluck('study_class_id')
            ->unique()
            ->filter();

        if ($classIds->isNotEmpty()) {
            DB::table('study_classes')->whereIn('id', $classIds)->delete();
        }

        DB::table('students')->whereIn('id', $studentIds)->delete();
    }
}
