<?php

namespace Tests\Feature\Course;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseEnrollConfig;
use App\Models\CourseTrack;
use App\Models\SubCategory;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class CourseControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
        Storage::fake('public');
    }

    private function createTrack(): CourseTrack
    {
        $category = Category::create(['name' => 'Electronics', 'status' => 'active']);
        $sub = SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Microcontrollers '.uniqid(),
            'slug' => 'microcontrollers-'.uniqid(),
            'status' => 'active',
        ]);

        return CourseTrack::create([
            'sub_category_id' => $sub->id,
            'name' => 'Arduino Track',
            'slug' => 'arduino-track-'.uniqid(),
            'status' => 'active',
        ]);
    }

    private function createCourse(array $attributes = []): Course
    {
        return Course::create(array_merge([
            'course_track_id' => $this->createTrack()->id,
            'title' => 'Intro to Arduino',
            'slug' => 'intro-to-arduino-'.uniqid(),
            'level' => 'beginner',
            'status' => 'active',
        ], $attributes));
    }

    // GET /dashboard/course/courses

    public function test_super_admin_can_view_courses_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/course/courses')
            ->assertOk();
    }

    public function test_admin_is_forbidden_from_courses(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/course/courses')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_courses(): void
    {
        $this->get('/dashboard/course/courses')
            ->assertRedirect('/login');
    }

    // GET show/create/edit pages

    public function test_super_admin_can_view_course_create_show_and_edit_pages(): void
    {
        $course = $this->createCourse();

        foreach ([['create'], ["{$course->id}"], ["{$course->id}/edit"]] as [$path]) {
            $this->actingAs($this->superAdmin())
                ->get("/dashboard/course/courses/{$path}")
                ->assertOk();
        }
    }

    // POST /dashboard/course/courses

    public function test_super_admin_can_create_a_course(): void
    {
        $track = $this->createTrack();

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/course/courses', [
                'course_track_id' => $track->id,
                'title' => 'Digital Logic',
                'level' => 'intermediate',
                'price' => 99.5,
                'status' => 'inactive',
            ])
            ->assertRedirect('/dashboard/course/courses')
            ->assertSessionHas('success', 'Course created successfully');

        $course = Course::where('title', 'Digital Logic')->firstOrFail();

        $this->assertSame($track->id, $course->course_track_id);
        $this->assertSame('digital-logic', $course->slug);
        $this->assertSame('intermediate', $course->level);
        $this->assertSame('inactive', $course->status);

        // The form price is stored on the course's default enroll config, not on courses.
        $this->assertDatabaseHas('course_enroll_configs', [
            'course_id' => $course->id,
            'time_id' => null,
            'course_price' => 99.5,
        ]);
    }

    public function test_store_defaults_level_and_status_when_omitted(): void
    {
        $track = $this->createTrack();

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/course/courses', [
                'course_track_id' => $track->id,
                'title' => 'Defaults Course',
            ])
            ->assertRedirect('/dashboard/course/courses');

        $course = Course::where('title', 'Defaults Course')->firstOrFail();

        $this->assertSame('beginner', $course->level);
        $this->assertSame('active', $course->status);

        $this->assertDatabaseHas('course_enroll_configs', [
            'course_id' => $course->id,
            'time_id' => null,
            'course_price' => 0,
        ]);
    }

    public function test_store_accepts_a_thumbnail_upload(): void
    {
        Storage::fake('public');
        $track = $this->createTrack();
        $thumbnail = UploadedFile::fake()->image('cover.jpg');

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/course/courses', [
                'course_track_id' => $track->id,
                'title' => 'Pictured Course',
                'thumbnail' => $thumbnail,
            ])
            ->assertRedirect('/dashboard/course/courses');

        $course = Course::where('title', 'Pictured Course')->firstOrFail();

        $this->assertNotNull($course->thumbnail);
        $this->assertStringStartsWith('courses/thumbnails/', $course->thumbnail);
        Storage::disk('public')->assertExists($course->thumbnail);
    }

    public function test_store_rejects_duplicate_titles(): void
    {
        $this->createCourse(['title' => 'Same Title']);

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/course/courses', [
                'course_track_id' => $this->createTrack()->id,
                'title' => 'Same Title',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_requires_an_existing_track(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/course/courses', [
                'course_track_id' => 99999,
                'title' => 'Orphaned Course',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['course_track_id']);
    }

    public function test_store_validates_level_and_price_ranges(): void
    {
        $track = $this->createTrack();

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/course/courses', [
                'course_track_id' => $track->id,
                'title' => 'Bad Values',
                'level' => 'expert',
                'price' => -5,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['level', 'price']);
    }

    // PUT /dashboard/course/courses/{course}

    public function test_super_admin_can_update_a_course_and_its_price_config(): void
    {
        $course = $this->createCourse();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/course/courses/{$course->id}", [
                'course_track_id' => $course->course_track_id,
                'title' => 'Updated Title',
                'level' => 'advanced',
                'price' => 150,
                'status' => 'inactive',
            ])
            ->assertRedirect('/dashboard/course/courses')
            ->assertSessionHas('success', 'Course updated successfully');

        $fresh = $course->fresh();

        $this->assertSame('Updated Title', $fresh->title);
        $this->assertSame('advanced', $fresh->level);
        $this->assertSame('inactive', $fresh->status);
        $this->assertDatabaseHas('course_enroll_configs', [
            'course_id' => $course->id,
            'time_id' => null,
            'course_price' => 150,
        ]);
    }

    // DELETE /dashboard/course/courses/{course}

    public function test_destroy_deletes_the_course_and_its_thumbnail_file(): void
    {
        Storage::fake('public');
        $course = $this->createCourse();
        $path = UploadedFile::fake()->image('old.jpg')->store('courses/thumbnails', 'public');
        $course->update(['thumbnail' => $path]);

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/course/courses/{$course->id}")
            ->assertRedirect('/dashboard/course/courses')
            ->assertSessionHas('success', 'Course deleted successfully');

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
