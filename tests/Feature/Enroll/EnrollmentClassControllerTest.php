<?php

namespace Tests\Feature\Enroll;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class EnrollmentClassControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createCourse(string $title = 'Networking Fundamentals'): Course
    {
        return Course::create([
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'status' => 'active',
        ]);
    }

    private function createPhysicalClassType(): ClassType
    {
        return ClassType::create(['type_name' => 'Network', 'is_active' => true]);
    }

    private function createOnlineClassType(): ClassType
    {
        return ClassType::create(['type_name' => 'Online Live', 'is_active' => true]);
    }

    /**
     * Builds the schedule grid (term + time + schedule + pivot) that
     * SaveStudyClassRequest validates term_id/time_id against.
     */
    private function createScheduleGrid(ClassType $classType): array
    {
        $term = Term::create(['term_name' => 'Mon & Tue']);
        $time = Time::create(['time_name' => '08:00 AM - 10:00 AM']);

        $schedule = Schedule::create([
            'class_type_id' => $classType->class_type_id,
            'term_id' => $term->id,
        ]);
        $schedule->times()->sync([$time->id]);

        return compact('term', 'time', 'schedule');
    }

    private function createStudyClass(array $attributes = []): StudyClass
    {
        $classType = $this->createPhysicalClassType();
        ['term' => $term, 'time' => $time] = $this->createScheduleGrid($classType);

        return StudyClass::create(array_merge([
            'title' => $this->createCourse()->title,
            'course_id' => Course::firstOrFail()->id,
            'class_type_id' => $classType->class_type_id,
            'term_id' => $term->id,
            'time_id' => $time->id,
            'status' => 'upcoming',
            'capacity' => 20,
            'price' => 100,
            'document_price' => 0,
        ], $attributes));
    }

    private function validPayload(): array
    {
        $course = $this->createCourse('Web Development');
        $classType = $this->createPhysicalClassType();
        ['term' => $term, 'time' => $time] = $this->createScheduleGrid($classType);

        return [
            'title' => 'Ignored Custom Title',
            'course_id' => $course->id,
            'class_type_id' => $classType->class_type_id,
            'term_id' => $term->id,
            'time_id' => $time->id,
            'status' => 'upcoming',
            'capacity' => 30,
            'price' => 120,
        ];
    }

    // GET /dashboard/enroll

    public function test_super_admin_can_view_the_class_list(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/enroll')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_enroll(): void
    {
        $this->get('/dashboard/enroll')
            ->assertRedirect('/login');
    }

    public function test_admin_can_view_the_class_list(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/enroll')
            ->assertOk();
    }

    public function test_class_list_search_filters_results_server_side(): void
    {
        $matchingCourse = $this->createCourse('Web Development');
        $otherCourse = $this->createCourse('Graphic Design');

        $this->createStudyClass([
            'title' => 'Web Development',
            'course_id' => $matchingCourse->id,
        ]);

        $this->createStudyClass([
            'title' => 'Graphic Design',
            'course_id' => $otherCourse->id,
        ]);

        $this->actingAs($this->superAdmin())
            ->get('/dashboard/enroll?search=Web%20Development')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('backend/students/ClassList')
                ->where('filters.search', 'Web Development')
                ->has('classes.data', 1)
                ->where('classes.data.0.title', 'Web Development'));
    }

    // GET create/show pages

    public function test_super_admin_can_view_create_and_show_pages(): void
    {
        $studyClass = $this->createStudyClass();

        $this->actingAs($this->superAdmin())
            ->get('/dashboard/enroll/create')
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get("/dashboard/enroll/view/{$studyClass->id}")
            ->assertOk();
    }

    public function test_guest_is_redirected_to_the_public_qr_join_page_from_the_dashboard_add_student_route(): void
    {
        $studyClass = $this->createStudyClass();

        $this->get("/dashboard/enroll/{$studyClass->id}/students/create")
            ->assertRedirect("/join-class/{$studyClass->id}");
    }

    public function test_public_qr_join_page_is_available_to_guests(): void
    {
        $studyClass = $this->createStudyClass();

        $this->get("/join-class/{$studyClass->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('frontend/class-join/JoinClass'));
    }

    public function test_public_qr_join_page_rejects_pre_end_classes(): void
    {
        $studyClass = $this->createStudyClass(['status' => 'pre_end']);

        $this->get("/join-class/{$studyClass->id}")
            ->assertRedirect('/student-register')
            ->assertSessionHas('error', 'This class is no longer accepting join requests.');
    }

    public function test_public_qr_join_page_marks_class_as_locked_for_the_same_browser_after_joining(): void
    {
        $studyClass = $this->createStudyClass();

        $this->withSession(['qr_joined_class_ids' => [$studyClass->id]])
            ->get("/join-class/{$studyClass->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('frontend/class-join/JoinClass')
                ->where('isLocked', true));
    }

    public function test_same_browser_cannot_join_the_same_class_twice_but_can_join_a_different_class(): void
    {
        $firstClass = $this->createStudyClass(['title' => 'Class One']);
        $secondClass = $this->createStudyClass(['title' => 'Class Two']);

        $lockedResponse = $this->withSession(['qr_joined_class_ids' => [$firstClass->id]])
            ->post("/join-class/{$firstClass->id}", [
                'name' => 'Repeat Student',
                'gender' => 'male',
                'phone' => '012345670',
            ]);

        $lockedResponse->assertRedirect("/join-class/{$firstClass->id}");
        $lockedResponse->assertSessionHas('error', 'You have already requested this class from this device.');

        $allowedResponse = $this->withSession(['qr_joined_class_ids' => [$firstClass->id]])
            ->post("/join-class/{$secondClass->id}", [
                'name' => 'Repeat Student',
                'gender' => 'male',
                'phone' => '012345671',
            ]);

        $allowedResponse->assertRedirect("/join-class/{$secondClass->id}");
        $allowedResponse->assertSessionHas('success', 'Your request was sent. An instructor will review it before approval.');

        $this->assertDatabaseHas('student_enrollments', [
            'study_class_id' => $secondClass->id,
            'source' => 'qr_code',
            'enrollment_status' => 'pending',
        ]);
    }

    // POST /dashboard/enroll

    public function test_store_creates_a_physical_class_using_course_title_and_room_capacity(): void
    {
        $payload = $this->validPayload();
        $floor = \App\Models\Floor::create(['name' => 'Ground', 'level' => 0]);
        $room = \App\Models\Room::create([
            'floor_id' => $floor->id,
            'room_number' => 'R-101',
            'capacity' => 15,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->post('/dashboard/enroll', array_merge($payload, [
                'room_id' => $room->id,
                'capacity' => 99,
            ]));

        $studyClass = StudyClass::where('course_id', $payload['course_id'])->firstOrFail();

        $response->assertRedirect('/dashboard/enroll')
            ->assertSessionHas('success', 'Class created successfully.');

        // The course title wins over any submitted title, and a physical class
        // inherits its room's capacity.
        $this->assertSame('Web Development', $studyClass->title);
        $this->assertSame(15, $studyClass->capacity);
        $this->assertSame($room->id, $studyClass->room_id);
        $this->assertSame('upcoming', $studyClass->status);
    }

    public function test_admin_can_create_a_class(): void
    {
        $payload = $this->validPayload();

        $this->actingAs($this->admin())
            ->post('/dashboard/enroll', $payload)
            ->assertRedirect('/dashboard/enroll');

        $this->assertDatabaseHas('study_classes', [
            'course_id' => $payload['course_id'],
            'price' => 120,
        ]);
    }

    public function test_store_nulls_the_room_and_keeps_capacity_for_online_classes(): void
    {
        $course = $this->createCourse('Online English');
        $onlineType = $this->createOnlineClassType();
        ['term' => $term, 'time' => $time] = $this->createScheduleGrid($onlineType);

        $floor = \App\Models\Floor::create(['name' => 'Ground', 'level' => 0]);
        $room = \App\Models\Room::create([
            'floor_id' => $floor->id,
            'room_number' => 'R-102',
            'capacity' => 5,
            'status' => 'available',
        ]);

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/enroll', [
                'title' => 'Whatever',
                'course_id' => $course->id,
                'class_type_id' => $onlineType->class_type_id,
                'term_id' => $term->id,
                'time_id' => $time->id,
                'room_id' => $room->id,
                'status' => 'upcoming',
                'capacity' => 40,
                'price' => 60,
            ])
            ->assertRedirect('/dashboard/enroll');

        $studyClass = StudyClass::where('course_id', $course->id)->firstOrFail();

        $this->assertNull($studyClass->room_id, 'Online classes must not keep a room');
        $this->assertSame(40, $studyClass->capacity, 'Online capacity comes from the payload, not a room');
    }

    public function test_store_rejects_a_term_that_is_not_scheduled_for_the_class_type(): void
    {
        $payload = $this->validPayload();
        $unscheduledTerm = Term::create(['term_name' => 'Sun Only']);

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/enroll', array_merge($payload, ['term_id' => $unscheduledTerm->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['term_id']);
    }

    public function test_store_requires_core_fields(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/enroll', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'course_id', 'class_type_id', 'term_id', 'time_id', 'status', 'capacity', 'price']);
    }

    public function test_store_validates_status_values(): void
    {
        $payload = $this->validPayload();
        $payload['status'] = 'paused';

        $this->actingAs($this->admin())
            ->postJson('/dashboard/enroll', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // GET edit page

    public function test_super_admin_can_view_the_edit_page(): void
    {
        $studyClass = $this->createStudyClass();

        $this->actingAs($this->superAdmin())
            ->get("/dashboard/enroll/edit/{$studyClass->id}")
            ->assertOk();
    }

    // PUT /dashboard/enroll/{studyClass}

    public function test_update_changes_price_status_and_capacity(): void
    {
        $studyClass = $this->createStudyClass(['capacity' => 20]);
        $course = $this->createCourse('Advanced Networking');

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/enroll/{$studyClass->id}", [
                'title' => 'Ignored',
                'course_id' => $course->id,
                'class_type_id' => $studyClass->class_type_id,
                'term_id' => $studyClass->term_id,
                'time_id' => $studyClass->time_id,
                'status' => 'active',
                'capacity' => 35,
                'price' => 200,
            ])
            ->assertRedirect('/dashboard/enroll')
            ->assertSessionHas('success', 'Class updated successfully.');

        $fresh = $studyClass->fresh();

        $this->assertSame('Advanced Networking', $fresh->title);
        $this->assertSame(200.0, (float) $fresh->price);
        $this->assertSame(35, $fresh->capacity);
        $this->assertSame('active', $fresh->status);
    }

    // POST /dashboard/enroll/{studyClass}/status

    public function test_update_status_maps_aliases_to_canonical_values(): void
    {
        $studyClass = $this->createStudyClass();

        $this->actingAs($this->superAdmin())
            ->post("/dashboard/enroll/{$studyClass->id}/status", ['status' => 'completed'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Class status updated successfully.');

        $this->assertSame('ended', $studyClass->fresh()->status);

        $this->actingAs($this->superAdmin())
            ->post("/dashboard/enroll/{$studyClass->id}/status", ['status' => 'inactive'])
            ->assertRedirect();

        $this->assertSame('pre_end', $studyClass->fresh()->status);
    }

    public function test_update_status_validates_unknown_statuses(): void
    {
        $studyClass = $this->createStudyClass();

        $this->actingAs($this->superAdmin())
            ->postJson("/dashboard/enroll/{$studyClass->id}/status", ['status' => 'warp-speed'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_instructor_can_approve_a_pending_qr_registration_for_own_class(): void
    {
        $instructor = $this->instructor();
        $studyClass = $this->createStudyClass(['teacher_id' => $instructor->id]);
        $student = Student::create([
            'full_name' => 'Pending Student',
            'gender' => 'male',
            'phone' => '012345679',
        ]);
        $enrollment = StudentEnrollment::create([
            'study_class_id' => $studyClass->id,
            'student_id' => $student->id,
            'enrollment_status' => 'pending',
            'payment_status' => 'unpaid',
            'source' => 'qr_code',
            'fee_amount' => 100,
            'document_fee_amount' => 0,
            'amount_paid' => 0,
        ]);

        $this->actingAs($instructor)
            ->post("/dashboard/enroll/enrollments/{$enrollment->id}/approve")
            ->assertRedirect();

        $this->assertSame('active', $enrollment->fresh()->enrollment_status);
    }

    public function test_instructor_can_bulk_approve_pending_qr_registrations_for_own_class(): void
    {
        $instructor = $this->instructor();
        $studyClass = $this->createStudyClass(['teacher_id' => $instructor->id, 'capacity' => 20]);

        $studentA = Student::create([
            'full_name' => 'Pending Student A',
            'gender' => 'male',
            'phone' => '012345680',
        ]);
        $studentB = Student::create([
            'full_name' => 'Pending Student B',
            'gender' => 'female',
            'phone' => '012345681',
        ]);

        $enrollmentA = StudentEnrollment::create([
            'study_class_id' => $studyClass->id,
            'student_id' => $studentA->id,
            'enrollment_status' => 'pending',
            'payment_status' => 'unpaid',
            'source' => 'qr_code',
            'fee_amount' => 100,
            'document_fee_amount' => 0,
            'amount_paid' => 0,
        ]);
        $enrollmentB = StudentEnrollment::create([
            'study_class_id' => $studyClass->id,
            'student_id' => $studentB->id,
            'enrollment_status' => 'pending',
            'payment_status' => 'unpaid',
            'source' => 'qr_code',
            'fee_amount' => 100,
            'document_fee_amount' => 0,
            'amount_paid' => 0,
        ]);

        $this->actingAs($instructor)
            ->postJson('/dashboard/enroll/enrollments/approve', [
                'enrollment_ids' => [$enrollmentA->id, $enrollmentB->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'approved_count' => 2]);

        $this->assertSame('active', $enrollmentA->fresh()->enrollment_status);
        $this->assertSame('active', $enrollmentB->fresh()->enrollment_status);
    }

    public function test_pre_end_class_cannot_accept_student_additions_or_collapse_actions(): void
    {
        $studyClass = $this->createStudyClass(['status' => 'pre_end']);

        $this->actingAs($this->superAdmin())
            ->get("/dashboard/enroll/{$studyClass->id}/students/create")
            ->assertStatus(422);

        $student = Student::create([
            'full_name' => 'Blocked Student',
            'gender' => 'male',
            'phone' => '012345682',
        ]);

        $this->actingAs($this->superAdmin())
            ->postJson("/dashboard/enroll/{$studyClass->id}/enrollments", [
                'student_id' => $student->id,
            ])
            ->assertStatus(422);

        $this->actingAs($this->superAdmin())
            ->postJson("/dashboard/enroll/{$studyClass->id}/instructors", [
                'instructor_id' => $this->instructor()->id,
                'owner_term_id' => $studyClass->term_id,
                'owner_subject' => 'Code',
                'instructor_term_id' => $studyClass->term_id,
                'instructor_subject' => 'Math',
            ])
            ->assertStatus(422);
    }

    // DELETE /dashboard/enroll/{studyClass}

    public function test_super_admin_destroy_deletes_the_class_and_its_enrollments(): void
    {
        $studyClass = $this->createStudyClass();
        $student = Student::create([
            'full_name' => 'Test Student',
            'gender' => 'male',
            'phone' => '012345678',
        ]);
        StudentEnrollment::create([
            'study_class_id' => $studyClass->id,
            'student_id' => $student->id,
            'enrollment_status' => 'active',
            'payment_status' => 'unpaid',
            'source' => 'manual',
            'fee_amount' => 100,
        ]);

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/enroll/{$studyClass->id}")
            ->assertRedirect('/dashboard/enroll')
            ->assertSessionHas('success', 'Class deleted successfully.');

        $this->assertDatabaseMissing('study_classes', ['id' => $studyClass->id]);
        $this->assertDatabaseMissing('student_enrollments', ['study_class_id' => $studyClass->id]);
    }
}
