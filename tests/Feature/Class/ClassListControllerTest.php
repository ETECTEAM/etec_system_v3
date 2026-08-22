<?php

namespace Tests\Feature\Class;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\StudyClass;
use App\Models\Term;
use App\Models\Time;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class ClassListControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createCourse(string $title = 'Networking Basics'): Course
    {
        return Course::create([
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'status' => 'active',
        ]);
    }

    private function createClass(array $attributes = []): StudyClass
    {
        return StudyClass::create(array_merge([
            'title' => $this->createCourse()->title,
            'course_id' => Course::firstOrFail()->id,
            'status' => 'upcoming',
            'capacity' => 20,
        ], $attributes));
    }

    // GET /dashboard/class-list

    public function test_super_admin_can_view_class_list_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/class-list')
            ->assertOk();
    }

    public function test_admin_can_view_class_list_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/class-list')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_class_list(): void
    {
        $this->get('/dashboard/class-list')
            ->assertRedirect('/login');
    }

    public function test_instructor_cannot_view_or_manage_class_list(): void
    {
        $studyClass = $this->createClass();

        foreach (['', 'create'] as $path) {
            $this->actingAs($this->instructor())
                ->get("/dashboard/class-list/{$path}")
                ->assertForbidden();
        }

        $this->actingAs($this->instructor())
            ->postJson('/dashboard/class-list', ['title' => 'Blocked', 'course_id' => 1])
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->deleteJson("/dashboard/class-list/{$studyClass->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('study_classes', ['id' => $studyClass->id]);
    }

    // GET create/show/edit pages

    public function test_super_admin_can_view_create_show_and_edit_pages(): void
    {
        $studyClass = $this->createClass();
        $id = $studyClass->id;

        foreach (['create', $id, "{$id}/edit"] as $path) {
            $this->actingAs($this->superAdmin())
                ->get("/dashboard/class-list/{$path}")
                ->assertOk();
        }
    }

    // POST /dashboard/class-list

    public function test_super_admin_can_create_a_class(): void
    {
        $course = $this->createCourse('Advanced Circuits');
        $teacher = $this->instructor();
        $term = Term::create(['term_name' => 'Mon & Tue']);
        $time = Time::create(['time_name' => '08:00 AM - 10:00 AM']);
        $classType = ClassType::create(['type_name' => 'Programming', 'is_active' => true]);

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/class-list', [
                'title' => 'Morning Circuit Class',
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'term_id' => $term->id,
                'time_id' => $time->id,
                'class_type_id' => $classType->class_type_id,
                'capacity' => 25,
                'status' => 'upcoming',
            ])
            ->assertRedirect('/dashboard/class-list')
            ->assertSessionHas('success', 'Class created successfully.');

        $studyClass = StudyClass::where('title', 'Morning Circuit Class')->firstOrFail();

        $this->assertSame($course->id, $studyClass->course_id);
        $this->assertSame($teacher->id, $studyClass->teacher_id);
        $this->assertSame(25, $studyClass->capacity);
        $this->assertSame('upcoming', $studyClass->status);
    }

    public function test_admin_can_create_a_minimal_class_with_only_title_and_course(): void
    {
        $course = $this->createCourse('Minimal Course');

        $this->actingAs($this->admin())
            ->post('/dashboard/class-list', [
                'title' => 'Bare Bones Class',
                'course_id' => $course->id,
            ])
            ->assertRedirect('/dashboard/class-list');

        $this->assertDatabaseHas('study_classes', [
            'title' => 'Bare Bones Class',
            'course_id' => $course->id,
        ]);
    }

    public function test_store_requires_title_and_existing_course(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/class-list', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'course_id']);

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/class-list', ['title' => 'Ghost Course', 'course_id' => 99999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['course_id']);
    }

    public function test_store_validates_status_against_allowed_values(): void
    {
        $course = $this->createCourse();

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/class-list', [
                'title' => 'Bad Status',
                'course_id' => $course->id,
                'status' => 'exploded',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // PUT /dashboard/class-list/{classList}

    public function test_super_admin_can_update_a_class(): void
    {
        $studyClass = $this->createClass(['capacity' => 10]);

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/class-list/{$studyClass->id}", [
                'title' => 'Renamed Class',
                'status' => 'active',
                'capacity' => 40,
            ])
            ->assertRedirect('/dashboard/class-list')
            ->assertSessionHas('success', 'Class updated successfully.');

        $fresh = $studyClass->fresh();

        $this->assertSame('Renamed Class', $fresh->title);
        $this->assertSame('active', $fresh->status);
        $this->assertSame(40, $fresh->capacity);
    }

    // DELETE /dashboard/class-list/{classList}

    public function test_super_admin_can_delete_a_class(): void
    {
        $studyClass = $this->createClass();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/class-list/{$studyClass->id}")
            ->assertRedirect('/dashboard/class-list')
            ->assertSessionHas('success', 'Class deleted successfully.');

        $this->assertDatabaseMissing('study_classes', ['id' => $studyClass->id]);
    }
}
