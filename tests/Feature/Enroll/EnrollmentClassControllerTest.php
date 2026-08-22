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

    public function test_admin_is_forbidden_from_the_super_admin_only_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/enroll')
            ->assertForbidden();
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
