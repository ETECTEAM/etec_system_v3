<?php

namespace Tests\Feature\Enroll;

use App\Models\Category;
use App\Models\ClassType;
use App\Models\Course;
use App\Models\CourseClassTypeStatus;
use App\Models\CourseEnrollConfig;
use App\Models\CourseTrack;
use App\Models\Schedule;
use App\Models\SubCategory;
use App\Models\Term;
use App\Models\Time;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class CourseClassTypeStatusTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    private ClassType $physical;

    private ClassType $scholarship;

    private ClassType $online;

    /** @var array<int, Schedule> class_type_id => its schedule */
    private array $schedules = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();

        $this->physical = ClassType::create(['type_name' => 'Physical Class']);
        $this->scholarship = ClassType::create(['type_name' => 'Scholarship Class']);
        $this->online = ClassType::create(['type_name' => 'Online Class']);

        // Schedule Management reference data: every class type has a term with a time.
        $term = Term::create(['term_name' => 'Mon & Thu']);
        $time = Time::create(['time_name' => '09:00 am - 10:30 am']);

        foreach ([$this->physical, $this->scholarship, $this->online] as $classType) {
            $schedule = Schedule::create(['class_type_id' => $classType->class_type_id, 'term_id' => $term->id]);
            $schedule->times()->attach($time->id);
            $this->schedules[$classType->class_type_id] = $schedule;
        }
    }

    // On an unmapped track by default, so the course is offered under all three
    // default class types; pass $mappedTo to pin it to one class type instead.
    private function createCourse(string $title = 'Basic IT', ?ClassType $mappedTo = null): Course
    {
        $category = Category::create(['name' => 'IT', 'status' => 'active']);
        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Software',
            'slug' => str($title)->slug()->toString().'-'.uniqid(),
            'status' => 'active',
        ]);
        $track = CourseTrack::create([
            'sub_category_id' => $subCategory->id,
            'class_type_id' => $mappedTo?->class_type_id,
            'name' => 'Office Track',
            'slug' => str($title)->slug()->toString().'-track-'.uniqid(),
            'status' => 'active',
        ]);

        return Course::create([
            'course_track_id' => $track->id,
            'title' => $title,
            'slug' => str($title)->slug()->toString(),
            'status' => 'active',
        ]);
    }

    // What toggling a time slot on creates: a schedule-scoped row.
    private function openSlot(Course $course, ClassType $classType, ?int $maxClasses = null): CourseEnrollConfig
    {
        $schedule = $this->schedules[$classType->class_type_id];

        return CourseEnrollConfig::create([
            'course_id' => $course->id,
            'schedule_id' => $schedule->id,
            'time_id' => $schedule->times()->value('times.id'),
            'status' => 'open',
            'max_classes' => $maxClasses,
        ]);
    }

    private function setStatus(Course $course, ClassType $classType, string $status)
    {
        return $this->actingAs($this->superAdmin())->putJson(
            "/dashboard/enroll/config/course/{$course->id}/class-type-status",
            ['class_type_id' => $classType->class_type_id, 'status' => $status],
        );
    }

    // The single course's class type nodes, as the Enroll Config page receives them.
    private function classTypesOnAdminPage(): Collection
    {
        $response = $this->actingAs($this->superAdmin())
            ->getJson('/dashboard/enroll/config/data')
            ->assertOk();

        return collect($response->json('categories.0.subCategories.0.tracks.0.courses.0.class_schedules'));
    }

    public function test_closing_one_class_type_leaves_the_others_open(): void
    {
        $course = $this->createCourse();

        $this->setStatus($course, $this->scholarship, 'closed')
            ->assertOk()
            ->assertJson(['class_type_id' => $this->scholarship->class_type_id, 'status' => 'closed']);

        $this->assertEquals([
            'Physical Class' => 'open',
            'Scholarship Class' => 'closed',
            'Online Class' => 'open',
        ], $this->classTypesOnAdminPage()->pluck('status', 'class_type_name')->all());
    }

    public function test_reopening_a_class_type_restores_it(): void
    {
        $course = $this->createCourse();

        $this->setStatus($course, $this->scholarship, 'closed')->assertOk();
        $this->setStatus($course, $this->scholarship, 'open')->assertOk();

        $this->assertEquals([
            'Physical Class' => 'open',
            'Scholarship Class' => 'open',
            'Online Class' => 'open',
        ], $this->classTypesOnAdminPage()->pluck('status', 'class_type_name')->all());

        // Toggling reuses the one row rather than piling up new ones.
        $this->assertDatabaseCount('course_class_type_statuses', 1);
    }

    public function test_closing_a_class_type_keeps_its_slots_and_class_limits(): void
    {
        $course = $this->createCourse();
        $slot = $this->openSlot($course, $this->scholarship, maxClasses: 3);

        $this->setStatus($course, $this->scholarship, 'closed')->assertOk();

        $this->assertDatabaseHas('course_enroll_configs', ['id' => $slot->id, 'max_classes' => 3]);

        $scholarship = $this->classTypesOnAdminPage()->firstWhere('class_type_name', 'Scholarship Class');
        $this->assertSame('closed', $scholarship['status']);
        $this->assertTrue($scholarship['terms'][0]['times'][0]['is_open']);
        $this->assertSame(3, $scholarship['terms'][0]['times'][0]['max_classes']);
    }

    public function test_a_class_type_the_course_is_not_offered_under_is_rejected(): void
    {
        $course = $this->createCourse(mappedTo: $this->physical);

        $this->setStatus($course, $this->online, 'closed')->assertUnprocessable();

        $this->assertDatabaseCount('course_class_type_statuses', 0);
    }

    public function test_status_must_be_open_or_closed(): void
    {
        $course = $this->createCourse();

        $this->setStatus($course, $this->physical, 'paused')->assertUnprocessable();

        $this->assertDatabaseCount('course_class_type_statuses', 0);
    }

    public function test_instructors_cannot_change_a_class_type_status(): void
    {
        $course = $this->createCourse();

        $this->actingAs($this->instructor())
            ->putJson("/dashboard/enroll/config/course/{$course->id}/class-type-status", [
                'class_type_id' => $this->physical->class_type_id,
                'status' => 'closed',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('course_class_type_statuses', 0);
    }

    public function test_student_register_hides_only_the_closed_class_type(): void
    {
        $course = $this->createCourse();
        foreach ([$this->physical, $this->scholarship, $this->online] as $classType) {
            $this->openSlot($course, $classType);
        }

        $status = CourseClassTypeStatus::create([
            'course_id' => $course->id,
            'class_type_id' => $this->scholarship->class_type_id,
            'status' => 'closed',
        ]);

        $classTypeIds = fn (array $expected) => fn ($types) => collect($types)
            ->pluck('class_type_id')->sort()->values()->all() === $expected;

        $this->get('/student-register')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('courses.0.class_types', $classTypeIds([
                $this->physical->class_type_id,
                $this->online->class_type_id,
            ])));

        $status->update(['status' => 'open']);

        $this->get('/student-register')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('courses.0.class_types', $classTypeIds([
                $this->physical->class_type_id,
                $this->scholarship->class_type_id,
                $this->online->class_type_id,
            ])));
    }

    // The migration turns the old course-wide Closed switch into per-class-type
    // rows so those courses stay hidden. It's exercised directly because
    // RefreshDatabase migrates an empty database before any data exists.
    private function carryOverClosedCourses(): void
    {
        $migration = require database_path('migrations/2026_09_19_000001_create_course_class_type_statuses_table.php');

        (new \ReflectionMethod($migration, 'carryOverClosedCourses'))->invoke($migration);
    }

    private function closedClassTypeIds(Course $course): array
    {
        return CourseClassTypeStatus::query()
            ->where('course_id', $course->id)
            ->where('status', 'closed')
            ->pluck('class_type_id')
            ->sort()
            ->values()
            ->all();
    }

    public function test_courses_closed_course_wide_stay_closed_under_every_class_type_they_offer(): void
    {
        $unmapped = $this->createCourse('Basic IT');
        $mapped = $this->createCourse('Photoshop', $this->physical);
        $stillOpen = $this->createCourse('Word Excel');

        // The old switch: the config row with no schedule and no time slot.
        CourseEnrollConfig::create(['course_id' => $unmapped->id, 'status' => 'closed']);
        CourseEnrollConfig::create(['course_id' => $mapped->id, 'status' => 'closed']);
        CourseEnrollConfig::create(['course_id' => $stillOpen->id, 'status' => 'open']);

        $this->carryOverClosedCourses();

        $this->assertSame([
            $this->physical->class_type_id,
            $this->scholarship->class_type_id,
            $this->online->class_type_id,
        ], $this->closedClassTypeIds($unmapped));
        $this->assertSame([$this->physical->class_type_id], $this->closedClassTypeIds($mapped));
        $this->assertSame([], $this->closedClassTypeIds($stillOpen));

        // The course-wide row keeps its prices and start date, so it's left as it was.
        $this->assertDatabaseHas('course_enroll_configs', ['course_id' => $unmapped->id, 'status' => 'closed']);
    }

    public function test_carry_over_also_closes_class_types_the_course_already_has_slots_for(): void
    {
        // Mapped to Physical, but still carrying a slot from before it was mapped:
        // public registration lists that slot, so it has to be closed too.
        $course = $this->createCourse(mappedTo: $this->physical);
        $this->openSlot($course, $this->online);
        CourseEnrollConfig::create(['course_id' => $course->id, 'status' => 'closed']);

        $this->carryOverClosedCourses();

        $this->assertSame([
            $this->physical->class_type_id,
            $this->online->class_type_id,
        ], $this->closedClassTypeIds($course));
    }
}
