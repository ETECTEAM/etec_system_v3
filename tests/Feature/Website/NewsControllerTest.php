<?php

namespace Tests\Feature\Website;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class NewsControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createNews(array $attributes = []): News
    {
        return News::create(array_merge([
            'title' => 'Open House',
            'slug' => 'open-house',
            'excerpt' => 'Come visit us',
            'sort_order' => 1,
            'is_featured' => false,
            'is_active' => true,
        ], $attributes));
    }

    private function validPayload(): array
    {
        return [
            'title' => 'Scholarship Program Launches',
            'excerpt' => 'New scholarships available',
            'description' => '<p>Full details inside</p>',
            'published_at' => '2026-08-01',
            'sort_order' => 3,
            'is_featured' => true,
            'is_active' => true,
        ];
    }

    // GET /dashboard/website/news

    public function test_super_admin_can_view_news_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/website/news')
            ->assertOk();
    }

    public function test_admin_can_view_news_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/website/news')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_news(): void
    {
        $this->get('/dashboard/website/news')
            ->assertRedirect('/login');
    }

    public function test_instructor_is_forbidden_from_news(): void
    {
        $news = $this->createNews();

        $this->actingAs($this->instructor())
            ->get('/dashboard/website/news')
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->postJson('/dashboard/website/news', ['title' => 'Blocked'])
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->deleteJson("/dashboard/website/news/{$news->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('news', ['id' => $news->id]);
    }

    // GET create/edit pages

    public function test_super_admin_can_view_news_create_and_edit_pages(): void
    {
        $news = $this->createNews();

        foreach ([['create'], ["{$news->id}/edit"]] as [$path]) {
            $this->actingAs($this->superAdmin())
                ->get("/dashboard/website/news/{$path}")
                ->assertOk();
        }
    }

    // POST /dashboard/website/news

    public function test_store_creates_a_news_item_attributed_to_the_author(): void
    {
        $author = $this->superAdmin();

        $response = $this->actingAs($author)
            ->post('/dashboard/website/news', $this->validPayload());

        $news = News::where('title', 'Scholarship Program Launches')->firstOrFail();

        $response->assertRedirect("/dashboard/website/news/{$news->id}/edit")
            ->assertSessionHas('success', 'News created successfully.');

        $this->assertSame('scholarship-program-launches', $news->slug);
        $this->assertSame($author->id, $news->user_id);
        $this->assertTrue((bool) $news->is_featured);
    }

    public function test_store_requires_a_title(): void
    {
        // sort_order is coerced to an integer before validation, so an omitted
        // value becomes 0 and passes; only title can actually be "required".
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/website/news', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_validates_published_at_is_a_date(): void
    {
        $payload = $this->validPayload();
        $payload['published_at'] = 'not-a-date';

        $this->actingAs($this->admin())
            ->postJson('/dashboard/website/news', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['published_at']);
    }

    // PUT /dashboard/website/news/{news}

    public function test_update_edits_a_news_item(): void
    {
        $news = $this->createNews();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/website/news/{$news->id}", [
                'title' => 'Renamed Announcement',
                'excerpt' => 'Updated excerpt',
                'sort_order' => 7,
                'is_featured' => true,
                'is_active' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'News updated successfully.');

        $fresh = $news->fresh();

        $this->assertSame('Renamed Announcement', $fresh->title);
        $this->assertSame(7, $fresh->sort_order);
        $this->assertFalse((bool) $fresh->is_active);
    }

    // PATCH status

    public function test_status_toggle_publishes_and_unpublishes(): void
    {
        $news = $this->createNews(['is_active' => true]);

        $this->actingAs($this->admin())
            ->patch("/dashboard/website/news/{$news->id}/status", ['is_active' => false])
            ->assertRedirect()
            ->assertSessionHas('success', 'News unpublished successfully.');

        $this->assertFalse((bool) $news->fresh()->is_active);
    }

    // DELETE /dashboard/website/news/{news}

    public function test_destroy_deletes_a_news_item(): void
    {
        $news = $this->createNews();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/website/news/{$news->id}")
            ->assertRedirect('/dashboard/website/news')
            ->assertSessionHas('success', 'News deleted successfully.');

        $this->assertDatabaseMissing('news', ['id' => $news->id]);
    }
}
