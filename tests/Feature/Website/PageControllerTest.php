<?php

namespace Tests\Feature\Website;

use App\Models\Menu;
use App\Models\Page;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createPage(array $attributes = []): Page
    {
        return Page::create(array_merge([
            'title' => 'About Us',
            'slug' => 'about-us',
            'is_active' => true,
        ], $attributes));
    }

    private function validPayload(): array
    {
        return [
            'title' => 'Contact',
            'overlay_opacity' => 40,
            'text_alignment' => 'center',
        ];
    }

    // Access control

    public function test_super_admin_can_view_pages_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/website/pages')
            ->assertOk();
    }

    public function test_admin_can_view_pages_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/website/pages')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_pages(): void
    {
        $this->get('/dashboard/website/pages')
            ->assertRedirect('/login');
    }

    public function test_instructor_is_forbidden_from_website_routes(): void
    {
        $page = $this->createPage();

        $this->actingAs($this->instructor())
            ->get('/dashboard/website/pages')
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->postJson('/dashboard/website/pages', ['title' => 'Blocked'])
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->deleteJson("/dashboard/website/pages/{$page->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('pages', ['id' => $page->id]);
    }

    // GET create/show/edit pages

    public function test_admin_can_view_page_create_show_and_edit_pages(): void
    {
        $page = $this->createPage();

        foreach ([['create'], ["{$page->id}"], ["{$page->id}/edit"]] as [$path]) {
            $this->actingAs($this->admin())
                ->get("/dashboard/website/pages/{$path}")
                ->assertOk();
        }
    }

    // POST /dashboard/website/pages

    public function test_store_creates_a_page_with_a_generated_slug_and_redirects_to_edit(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->post('/dashboard/website/pages', [
                'title' => 'Admissions Guide',
                'overlay_opacity' => 30,
                'text_alignment' => 'left',
                'is_active' => true,
            ]);

        $page = Page::where('title', 'Admissions Guide')->firstOrFail();

        $response->assertRedirect("/dashboard/website/pages/{$page->id}/edit")
            ->assertSessionHas('success', 'Page created successfully.');

        $this->assertSame('admissions-guide', $page->slug);
        $this->assertTrue($page->is_active);
    }

    public function test_slug_generation_uniquifies_collisions(): void
    {
        $this->createPage(['title' => 'Team', 'slug' => 'team']);

        $this->actingAs($this->admin())
            ->post('/dashboard/website/pages', [
                'title' => 'Team',
                'overlay_opacity' => 0,
                'text_alignment' => 'left',
            ])
            ->assertRedirect();

        $slugs = Page::where('title', 'Team')->pluck('slug')->sort()->values();

        $this->assertSame(['team', 'team-2'], $slugs->all());
    }

    public function test_store_requires_hero_settings(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/website/pages', ['title' => 'No Hero Config'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['overlay_opacity', 'text_alignment']);
    }

    public function test_store_validates_text_alignment_values(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/website/pages', array_merge($this->validPayload(), [
                'text_alignment' => 'diagonal',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['text_alignment']);

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/website/pages', array_merge($this->validPayload(), [
                'overlay_opacity' => 101,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['overlay_opacity']);
    }

    // PUT /dashboard/website/pages/{page}

    public function test_update_renames_a_page(): void
    {
        $page = $this->createPage();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/website/pages/{$page->id}", [
                'title' => 'Renamed Page',
                'is_active' => false,
                'overlay_opacity' => 10,
                'text_alignment' => 'right',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Page updated successfully.');

        $fresh = $page->fresh();

        $this->assertSame('Renamed Page', $fresh->title);
        $this->assertFalse($fresh->is_active);
    }

    // PATCH status

    public function test_status_toggle_updates_the_page(): void
    {
        $page = $this->createPage(['is_active' => false]);

        $this->actingAs($this->admin())
            ->patch("/dashboard/website/pages/{$page->id}/status", ['is_active' => true])
            ->assertRedirect()
            ->assertSessionHas('success', 'Page activated successfully.');

        $this->assertTrue($page->fresh()->is_active);
    }

    // DELETE /dashboard/website/pages/{page}

    public function test_destroy_deletes_an_unlinked_page(): void
    {
        $page = $this->createPage();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/website/pages/{$page->id}")
            ->assertRedirect('/dashboard/website/pages')
            ->assertSessionHas('success', 'Page deleted successfully.');

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_destroy_is_blocked_when_a_menu_references_the_page(): void
    {
        $page = $this->createPage();
        Menu::create([
            'name' => 'About Menu',
            'page_id' => $page->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/website/pages/{$page->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('pages', ['id' => $page->id]);
    }
}
