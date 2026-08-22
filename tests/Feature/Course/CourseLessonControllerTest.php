<?php

namespace Tests\Feature\Course;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\SubCategory;
use App\Models\CourseTrack;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class CourseLessonControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createCourse(): Course
    {
        $category = Category::create(['name' => 'Electronics '.uniqid(), 'status' => 'active']);
        $sub = SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Microcontrollers '.uniqid(),
            'slug' => 'microcontrollers-'.uniqid(),
            'status' => 'active',
        ]);
        $track = CourseTrack::create([
            'sub_category_id' => $sub->id,
            'name' => 'Arduino Track '.uniqid(),
            'slug' => 'arduino-track-'.uniqid(),
            'status' => 'active',
        ]);

        return Course::create([
            'course_track_id' => $track->id,
            'title' => 'Intro to Arduino',
            'slug' => 'intro-to-arduino-'.uniqid(),
            'level' => 'beginner',
            'status' => 'active',
        ]);
    }

    private function createLesson(array $attributes = []): CourseLesson
    {
        return CourseLesson::create(array_merge([
            'course_id' => $this->createCourse()->id,
            'title' => 'Blink LED',
            'slug' => 'blink-led',
            'duration' => 0,
            'order_number' => 1,
            'status' => 'active',
        ], $attributes));
    }

    // GET /dashboard/course/lessons

    public function test_super_admin_can_view_lessons_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/course/lessons')
            ->assertOk();
    }

    public function test_admin_is_forbidden_from_lessons(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/course/lessons')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_lessons(): void
    {
        $this->get('/dashboard/course/lessons')
            ->assertRedirect('/login');
    }

    // GET show/create/edit pages

    public function test_super_admin_can_view_lesson_create_show_and_edit_pages(): void
    {
        $lesson = $this->createLesson();

        foreach ([['create'], ["{$lesson->id}"], ["{$lesson->id}/edit"]] as [$path]) {
            $this->actingAs($this->superAdmin())
                ->get("/dashboard/course/lessons/{$path}")
                ->assertOk();
        }
    }

    // POST /dashboard/course/lessons

    public function test_super_admin_can_create_a_lesson_with_explicit_order(): void
    {
        $course = $this->createCourse();

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/course/lessons', [
                'course_id' => $course->id,
                'title' => 'Reading Schematics',
                'description' => 'How to read schematics',
                'video_url' => 'https://youtube.com/watch?v=abc123',
                'duration' => 45,
                'order_number' => 5,
                'status' => 'inactive',
            ])
            ->assertRedirect('/dashboard/course/lessons')
            ->assertSessionHas('success', 'Lesson created successfully');

        $lesson = CourseLesson::where('title', 'Reading Schematics')->firstOrFail();

        $this->assertSame($course->id, $lesson->course_id);
        $this->assertSame('reading-schematics', $lesson->slug);
        $this->assertSame(45, $lesson->duration);
        $this->assertSame(5, $lesson->order_number);
        $this->assertSame('inactive', $lesson->status);
    }

    public function test_store_auto_assigns_the_next_order_number_per_course(): void
    {
        $course = $this->createCourse();

        $first = $this->createLesson(['course_id' => $course->id, 'title' => 'First', 'slug' => 'first', 'order_number' => 1]);
        $second = $this->createLesson(['course_id' => $course->id, 'title' => 'Second', 'slug' => 'second', 'order_number' => 2]);

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/course/lessons', [
                'course_id' => $course->id,
                'title' => 'Third Lesson',
            ])
            ->assertRedirect('/dashboard/course/lessons');

        $third = CourseLesson::where('title', 'Third Lesson')->firstOrFail();

        $this->assertSame($second->order_number + 1, $third->order_number);
    }

    public function test_store_validates_video_url_format(): void
    {
        $course = $this->createCourse();

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/course/lessons', [
                'course_id' => $course->id,
                'title' => 'Bad Video URL',
                'video_url' => 'not-a-url',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['video_url']);
    }

    public function test_store_requires_existing_course_and_title(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/course/lessons', [
                'course_id' => 99999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['course_id', 'title']);
    }

    // PUT /dashboard/course/lessons/{lesson}

    public function test_super_admin_can_update_a_lesson(): void
    {
        $lesson = $this->createLesson();
        $otherCourse = $this->createCourse();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/course/lessons/{$lesson->id}", [
                'course_id' => $otherCourse->id,
                'title' => 'Rewritten Lesson',
                'duration' => 90,
                'order_number' => 3,
                'status' => 'inactive',
            ])
            ->assertRedirect('/dashboard/course/lessons')
            ->assertSessionHas('success', 'Lesson updated successfully');

        $fresh = $lesson->fresh();

        $this->assertSame($otherCourse->id, $fresh->course_id);
        $this->assertSame('Rewritten Lesson', $fresh->title);
        $this->assertSame('rewritten-lesson', $fresh->slug);
        $this->assertSame(90, $fresh->duration);
        $this->assertSame(3, $fresh->order_number);
        $this->assertSame('inactive', $fresh->status);
    }

    // DELETE /dashboard/course/lessons/{lesson}

    public function test_super_admin_can_delete_a_lesson(): void
    {
        $lesson = $this->createLesson();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/course/lessons/{$lesson->id}")
            ->assertRedirect('/dashboard/course/lessons')
            ->assertSessionHas('success', 'Lesson deleted successfully');

        $this->assertDatabaseMissing('course_lessons', ['id' => $lesson->id]);
    }
}
