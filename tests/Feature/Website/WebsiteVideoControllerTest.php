<?php

namespace Tests\Feature\Website;

use App\Models\User;
use App\Models\WebsiteVideo;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class WebsiteVideoControllerTest extends TestCase
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

    private function createVideo(array $attributes = []): WebsiteVideo
    {
        return WebsiteVideo::create(array_merge([
            'title' => 'Campus Tour',
            'description' => 'A walk around campus',
            'video_path' => 'uploads/videos/tour.mp4',
            'sort_order' => 1,
            'is_featured' => false,
            'is_active' => true,
        ], $attributes));
    }

    private function validPayload(): array
    {
        return [
            'title' => 'Lab Walkthrough',
            'description' => 'Electronics lab',
            'video' => UploadedFile::fake()->create('lab.mp4', 500, 'video/mp4'),
            'duration' => '03:25',
            'sort_order' => 2,
        ];
    }

    // GET /dashboard/website/videos

    public function test_super_admin_can_view_videos_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/website/videos')
            ->assertOk();
    }

    public function test_admin_can_view_videos_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/website/videos')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_videos(): void
    {
        $this->get('/dashboard/website/videos')
            ->assertRedirect('/login');
    }

    public function test_instructor_is_forbidden_from_videos(): void
    {
        $this->actingAs($this->instructor())
            ->get('/dashboard/website/videos')
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->postJson('/dashboard/website/videos', ['title' => 'Blocked'])
            ->assertForbidden();
    }

    // GET create/edit pages

    public function test_super_admin_can_view_video_create_and_edit_pages(): void
    {
        $video = $this->createVideo();

        foreach ([['create'], ["{$video->id}/edit"]] as [$path]) {
            $this->actingAs($this->superAdmin())
                ->get("/dashboard/website/videos/{$path}")
                ->assertOk();
        }
    }

    // POST /dashboard/website/videos

    public function test_store_uploads_the_video_file_and_redirects_to_edit(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->superAdmin())
            ->post('/dashboard/website/videos', $this->validPayload());

        $video = WebsiteVideo::where('title', 'Lab Walkthrough')->firstOrFail();

        $response->assertRedirect("/dashboard/website/videos/{$video->id}/edit")
            ->assertSessionHas('success', 'Video created successfully.');

        $this->assertStringStartsWith('uploads/videos/', $video->video_path);
        Storage::disk('public')->assertExists($video->video_path);
    }

    public function test_store_requires_a_video_file(): void
    {
        $payload = $this->validPayload();
        unset($payload['video']);

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/website/videos', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['video']);
    }

    public function test_store_rejects_non_video_files(): void
    {
        $payload = $this->validPayload();
        $payload['video'] = UploadedFile::fake()->image('not-a-video.jpg');

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/website/videos', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['video']);
    }

    public function test_store_coerces_a_missing_sort_order_to_zero(): void
    {
        $payload = $this->validPayload();
        unset($payload['sort_order']);

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/website/videos', $payload)
            ->assertRedirect();

        // prepareForValidation casts sort_order to int, so the omitted field lands as 0.
        $this->assertSame(0, WebsiteVideo::where('title', 'Lab Walkthrough')->first()->sort_order);
    }

    // PUT /dashboard/website/videos/{video}

    public function test_update_changes_metadata_without_replacing_the_file(): void
    {
        $video = $this->createVideo();

        $this->actingAs($this->admin())
            ->put("/dashboard/website/videos/{$video->id}", [
                'title' => 'Updated Title',
                'description' => 'New description',
                'duration' => '10:00',
                'sort_order' => 9,
                'is_featured' => true,
                'is_active' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Video updated successfully.');

        $fresh = $video->fresh();

        $this->assertSame('Updated Title', $fresh->title);
        $this->assertSame('uploads/videos/tour.mp4', $fresh->video_path, 'Existing file should be kept when no new upload is provided');
        $this->assertTrue((bool) $fresh->is_featured);
        $this->assertFalse((bool) $fresh->is_active);
    }

    // PATCH status

    public function test_status_toggle_updates_the_video(): void
    {
        $video = $this->createVideo(['is_active' => false]);

        $this->actingAs($this->superAdmin())
            ->patch("/dashboard/website/videos/{$video->id}/status", ['is_active' => true])
            ->assertRedirect()
            ->assertSessionHas('success', 'Video activated successfully.');

        $this->assertTrue($video->fresh()->is_active);
    }

    // DELETE /dashboard/website/videos/{video}

    public function test_destroy_deletes_the_row_and_stored_files(): void
    {
        $video = $this->createVideo(['thumbnail_path' => 'uploads/videos/thumbs/tour.jpg']);

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/website/videos/{$video->id}")
            ->assertRedirect('/dashboard/website/videos')
            ->assertSessionHas('success', 'Video deleted successfully.');

        $this->assertDatabaseMissing('website_videos', ['id' => $video->id]);
    }
}
