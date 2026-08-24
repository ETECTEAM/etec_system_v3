<?php

namespace Tests\Unit\Website;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseTrack;
use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\InstructorScheduleBlock;
use App\Models\Notification;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\SubCategory;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use App\Modules\Website\Actions\RegisterStudentForSchedule;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegisterStudentForScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Event::fake();
    }

    private function term(string $name = 'Monday'): Term
    {
        return Term::create(['term_name' => $name]);
    }

    private function time(Term $term, string $name): Time
    {
        return Time::create(['term_id' => $term->id, 'time_name' => $name]);
    }

    private function course(?CourseTrack $track = null): Course
    {
        return Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course-'.uniqid(),
            'course_track_id' => $track?->id,
            'status' => 'active',
        ]);
    }

    // See RegisterStudentForSchedule's availableInstructor(): it filters on
    // whereHas('user', fn ($q) => $q->where('status', 'active')), so this
    // must be explicit regardless of the users.status column's DB default -
    // UserFactory doesn't set it, and Eloquent's create() doesn't sync DB
    // column defaults back into the in-memory model.
    private function instructor(array $overrides = []): InstructorData
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('instructor');

        return InstructorData::create(array_merge([
            'user_id' => $user->id,
            'full_name' => 'Instructor '.$user->id,
            'available_for_class' => true,
            'status' => true,
        ], $overrides));
    }

    private function availability(InstructorData $instructor, int $dayOfWeek, string $start, string $end): InstructorAvailability
    {
        return InstructorAvailability::create([
            'instructor_id' => $instructor->id,
            'day_of_week' => $dayOfWeek,
            'employment_type' => 'full_time',
            'shift_group' => 'custom',
            'period' => 'daytime',
            'start_time' => $start,
            'end_time' => $end,
            'is_active' => true,
        ]);
    }

    private function openClass(Course $course, Term $term, Time $time, ?int $teacherId = null): StudyClass
    {
        return StudyClass::create([
            'title' => $course->title,
            'course_id' => $course->id,
            'teacher_id' => $teacherId,
            'term_id' => $term->id,
            'time_id' => $time->id,
            'status' => 'upcoming',
            'capacity' => 10,
            'price' => 100,
            'document_price' => 5,
            'enrollment_start_date' => now()->toDateString(),
        ]);
    }

    private function registrationData(Course $course, Term $term, Time $time, string $phone = '012345678'): array
    {
        return [
            'name' => 'Test Student',
            'gender' => 'male',
            'phone' => $phone,
            'course_id' => $course->id,
            'term_id' => $term->id,
            'time_id' => $time->id,
        ];
    }

    // 2. Manual block makes instructor unavailable - the only candidate is
    // blocked for this exact day/time, so the class stays teacherless.
    public function test_manual_block_makes_the_only_candidate_instructor_unavailable(): void
    {
        $term = $this->term('Monday');
        $time = $this->time($term, '09:00 AM - 10:30 AM');
        $course = $this->course();
        $instructor = $this->instructor();
        $this->availability($instructor, 1, '08:00', '12:00');

        InstructorScheduleBlock::create([
            'instructor_id' => $instructor->id,
            'day_of_week' => 1,
            'time_id' => $time->id,
            'status' => 'active',
        ]);

        $studyClass = $this->openClass($course, $term, $time);

        app(RegisterStudentForSchedule::class)->handle($this->registrationData($course, $term, $time));

        $this->assertNull($studyClass->fresh()->teacher_id);
    }

    public function test_instructor_without_working_availability_is_not_selected(): void
    {
        $term = $this->term('Monday');
        $time = $this->time($term, '09:00 AM - 10:30 AM');
        $course = $this->course();
        $instructor = $this->instructor();
        $studyClass = $this->openClass($course, $term, $time);

        app(RegisterStudentForSchedule::class)->handle($this->registrationData($course, $term, $time));

        $this->assertNull($studyClass->fresh()->teacher_id);
        $this->assertDatabaseMissing('study_classes', [
            'teacher_id' => $instructor->user_id,
            'term_id' => $term->id,
            'time_id' => $time->id,
        ]);
    }

    // 3. Another working slot (different time_id, same day) remains available
    // even though the instructor is blocked on a different slot.
    public function test_instructor_remains_available_for_a_different_time_slot(): void
    {
        $term = $this->term('Monday');
        $blockedTime = $this->time($term, '09:00 AM - 10:30 AM');
        $openTime = $this->time($term, '10:30 AM - 12:00 PM');
        $course = $this->course();
        $instructor = $this->instructor();
        $this->availability($instructor, 1, '08:00', '12:00');

        InstructorScheduleBlock::create([
            'instructor_id' => $instructor->id,
            'day_of_week' => 1,
            'time_id' => $blockedTime->id,
            'status' => 'active',
        ]);

        $studyClass = $this->openClass($course, $term, $openTime);

        app(RegisterStudentForSchedule::class)->handle($this->registrationData($course, $term, $openTime));

        $this->assertSame($instructor->user_id, $studyClass->fresh()->teacher_id);
    }

    // 8. Existing StudyClass conflict behavior still works, unaffected by
    // manual-block wiring - an instructor already teaching another class at
    // the exact same term+time is still excluded even with no block at all.
    public function test_instructor_with_an_existing_class_conflict_is_still_excluded(): void
    {
        $term = $this->term('Monday');
        $time = $this->time($term, '09:00 AM - 10:30 AM');
        $course = $this->course();
        $otherCourse = $this->course();
        $instructor = $this->instructor();
        $this->availability($instructor, 1, '08:00', '12:00');

        // Instructor already teaches a different class at this exact term+time.
        $this->openClass($otherCourse, $term, $time, $instructor->user_id);

        $studyClass = $this->openClass($course, $term, $time);

        app(RegisterStudentForSchedule::class)->handle($this->registrationData($course, $term, $time));

        $this->assertNull($studyClass->fresh()->teacher_id);
    }

    // 9. Existing instructor skill matching (bestFieldMatch) still works -
    // between two equally-available, conflict-free, unblocked instructors,
    // the one whose specialization matches the course's category is chosen.
    public function test_specialization_matching_still_prefers_the_best_field_match(): void
    {
        $term = $this->term('Monday');
        $time = $this->time($term, '09:00 AM - 10:30 AM');

        $category = Category::create(['name' => 'Graphic Design', 'status' => 'active']);
        $subCategory = SubCategory::create(['category_id' => $category->id, 'name' => 'Graphic & UI/UX Design', 'slug' => 'graphic-ui-ux-design', 'status' => 'active']);
        $track = CourseTrack::create(['sub_category_id' => $subCategory->id, 'name' => 'Graphic Design Track', 'slug' => 'graphic-design-track', 'status' => 'active']);
        $course = $this->course($track);

        $matchingInstructor = $this->instructor(['specialization' => ['Graphic Design']]);
        $this->availability($matchingInstructor, 1, '08:00', '12:00');

        $otherInstructor = $this->instructor(['specialization' => ['Network & Systems']]);
        $this->availability($otherInstructor, 1, '08:00', '12:00');

        $studyClass = $this->openClass($course, $term, $time);

        app(RegisterStudentForSchedule::class)->handle($this->registrationData($course, $term, $time));

        $this->assertSame($matchingInstructor->user_id, $studyClass->fresh()->teacher_id);
    }

    private function parkRegistration(Course $course, Term $term, Time $time): void
    {
        // No rooms and no instructors exist in this suite, so once the open
        // class has no seat left, createClass() cannot build another one and
        // the registration is parked as a classless (unassigned) enrollment.
        app(RegisterStudentForSchedule::class)->handle($this->registrationData($course, $term, $time));
    }

    // Neither a room nor an instructor exists in this suite, so a parked
    // registration is flagged with both resources missing.
    public function test_failed_registration_creates_an_unassigned_enrollment_and_notifies_admins(): void
    {
        $term = $this->term('Monday');
        $time = $this->time($term, '09:00 AM - 10:30 AM');
        $course = $this->course();

        $fullClass = $this->openClass($course, $term, $time);
        $fullClass->update(['capacity' => 0]);

        $this->parkRegistration($course, $term, $time);

        $enrollment = StudentEnrollment::query()->where('enrollment_status', 'unassigned')->sole();
        $this->assertNull($enrollment->study_class_id);
        $this->assertTrue($enrollment->no_room_and_instructor);
        $this->assertFalse($enrollment->no_room);
        $this->assertFalse($enrollment->no_instructor);

        $notification = Notification::query()->where('type', 'unassigned_registration')->sole();
        $this->assertStringContainsString('Test Student', $notification->message);
        $this->assertStringContainsString($course->title, $notification->message);
    }

    // A free instructor exists but no room does, for a physical class type -
    // the parked enrollment should report only the room as missing.
    public function test_parked_registration_reports_no_room_only_when_an_instructor_is_free(): void
    {
        $term = $this->term('Monday');
        $time = $this->time($term, '09:00 AM - 10:30 AM');
        $course = $this->course();
        $instructor = $this->instructor();
        $this->availability($instructor, 1, '08:00', '12:00');

        $fullClass = $this->openClass($course, $term, $time);
        $fullClass->update(['capacity' => 0]);

        $this->parkRegistration($course, $term, $time);

        $enrollment = StudentEnrollment::query()->where('enrollment_status', 'unassigned')->sole();
        $this->assertTrue($enrollment->no_room);
        $this->assertFalse($enrollment->no_instructor);
        $this->assertFalse($enrollment->no_room_and_instructor);
    }
}
