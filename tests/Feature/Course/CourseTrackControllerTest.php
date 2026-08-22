<?php

namespace Tests\Feature\Course;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseTrack;
use App\Models\SubCategory;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class CourseTrackControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createSubCategory(): SubCategory
    {
        $category = Category::create(['name' => 'Electronics', 'status' => 'active']);

        return SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Microcontrollers '.uniqid(),
            'slug' => 'microcontrollers-'.uniqid(),
            'status' => 'active',
        ]);
    }

    private function createTrack(array $attributes = []): CourseTrack
    {
        return CourseTrack::create(array_merge([
            'sub_category_id' => $this->createSubCategory()->id,
            'name' => 'Arduino Track',
            'slug' => 'arduino-track',
            'status' => 'active',
        ], $attributes));
    }

    // GET /dashboard/course/tracks

    public function test_super_admin_can_view_tracks_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/course/tracks')
            ->assertOk();
    }

    public function test_admin_is_forbidden_from_tracks(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/course/tracks')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_tracks(): void
    {
        $this->get('/dashboard/course/tracks')
            ->assertRedirect('/login');
    }

    // GET show/create/edit pages

    public function test_super_admin_can_view_track_create_show_and_edit_pages(): void
    {
        $track = $this->createTrack();

        foreach ([['create'], ["{$track->id}"], ["{$track->id}/edit"]] as [$path]) {
            $this->actingAs($this->superAdmin())
                ->get("/dashboard/course/tracks/{$path}")
                ->assertOk();
        }
    }

    // POST /dashboard/course/tracks

    public function test_super_admin_can_create_a_track(): void
    {
        $sub = $this->createSubCategory();

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/course/tracks', [
                'sub_category_id' => $sub->id,
                'name' => 'Embedded Track',
                'description' => 'Deep dive embedded',
                'status' => 'inactive',
            ])
            ->assertRedirect('/dashboard/course/tracks')
            ->assertSessionHas('success', 'Track created successfully');

        $track = CourseTrack::where('name', 'Embedded Track')->firstOrFail();

        $this->assertSame($sub->id, $track->sub_category_id);
        $this->assertSame('embedded-track', $track->slug);
        $this->assertSame('inactive', $track->status);
    }

    public function test_store_rejects_duplicate_track_names(): void
    {
        $this->createTrack(['name' => 'Duplicated']);

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/course/tracks', [
                'sub_category_id' => $this->createSubCategory()->id,
                'name' => 'Duplicated',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_requires_existing_sub_category(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/course/tracks', [
                'sub_category_id' => 99999,
                'name' => 'Orphan Track',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sub_category_id']);
    }

    // PUT /dashboard/course/tracks/{track}

    public function test_super_admin_can_update_a_track(): void
    {
        $track = $this->createTrack();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/course/tracks/{$track->id}", [
                'sub_category_id' => $track->sub_category_id,
                'name' => 'Renamed Track',
                'status' => 'inactive',
            ])
            ->assertRedirect('/dashboard/course/tracks')
            ->assertSessionHas('success', 'Track updated successfully');

        $fresh = $track->fresh();

        $this->assertSame('Renamed Track', $fresh->name);
        $this->assertSame('renamed-track', $fresh->slug);
        $this->assertSame('inactive', $fresh->status);
    }

    // DELETE /dashboard/course/tracks/{track}

    public function test_super_admin_can_delete_an_empty_track(): void
    {
        $track = $this->createTrack();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/course/tracks/{$track->id}")
            ->assertRedirect('/dashboard/course/tracks')
            ->assertSessionHas('success', 'Track deleted successfully');

        $this->assertDatabaseMissing('course_tracks', ['id' => $track->id]);
    }

    public function test_delete_is_blocked_when_the_track_has_courses(): void
    {
        $track = $this->createTrack();

        Course::create([
            'course_track_id' => $track->id,
            'title' => 'Attached Course',
            'slug' => 'attached-course',
            'status' => 'active',
        ]);

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/course/tracks/{$track->id}")
            ->assertRedirect()
            ->assertSessionHas('error', 'Cannot delete track with existing courses.');

        $this->assertDatabaseHas('course_tracks', ['id' => $track->id]);
    }
}
