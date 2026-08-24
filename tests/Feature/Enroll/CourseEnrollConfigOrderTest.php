<?php

namespace Tests\Feature\Enroll;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseTrack;
use App\Models\SubCategory;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class CourseEnrollConfigOrderTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createCategorizedCourse(string $title, ?int $enrollOrder = null): Course
    {
        $category = Category::create(['name' => 'IT', 'status' => 'active']);
        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Software',
            // Slug is globally unique on sub_categories - one chain per course.
            'slug' => str($title)->slug()->toString().'-'.uniqid(),
            'status' => 'active',
        ]);
        $track = CourseTrack::create([
            'sub_category_id' => $subCategory->id,
            'name' => 'Office Track',
            'slug' => str($title)->slug()->toString().'-track-'.uniqid(),
            'status' => 'active',
        ]);

        return Course::create([
            'course_track_id' => $track->id,
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'status' => 'active',
            'enroll_order' => $enrollOrder,
        ]);
    }

    public function test_super_admin_can_set_a_course_display_order(): void
    {
        $course = $this->createCategorizedCourse('Basic IT');

        $response = $this->actingAs($this->superAdmin())
            ->putJson("/dashboard/enroll/config/course/{$course->id}/order", ['enroll_order' => 2]);

        $response->assertOk()
            ->assertJson(['id' => $course->id, 'enroll_order' => 2]);

        $this->assertSame(2, $course->fresh()->enroll_order);
    }

    public function test_course_order_must_be_at_least_one(): void
    {
        $course = $this->createCategorizedCourse('Basic IT');

        $this->actingAs($this->superAdmin())
            ->putJson("/dashboard/enroll/config/course/{$course->id}/order", ['enroll_order' => 0])
            ->assertUnprocessable();

        $this->assertNull($course->fresh()->enroll_order);
    }

    public function test_clearing_the_order_is_allowed(): void
    {
        $course = $this->createCategorizedCourse('Basic IT', 3);

        $this->actingAs($this->superAdmin())
            ->putJson("/dashboard/enroll/config/course/{$course->id}/order", ['enroll_order' => null])
            ->assertOk();

        $this->assertNull($course->fresh()->enroll_order);
    }

    public function test_student_register_lists_ordered_courses_first(): void
    {
        // Created alphabetically-first on purpose: ordering must come from
        // enroll_order, not from title or insertion order.
        $unordered = $this->createCategorizedCourse('Advanced Networking');
        $second = $this->createCategorizedCourse('Office Word Excel', 2);
        $first = $this->createCategorizedCourse('Basic IT', 1);

        $this->get('/student-register')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('frontend/student-register/StudentRegister')
                ->has('courses', 3)
                ->where('courses.0.id', $first->id)
                ->where('courses.1.id', $second->id)
                ->where('courses.2.id', $unordered->id));
    }
}
