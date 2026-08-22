<?php

namespace Tests\Feature\Website;

use App\Models\Page;
use App\Models\Menu;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class MenuControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createPage(): Page
    {
        return Page::create([
            'title' => 'Home',
            'slug' => 'home-'.uniqid(),
            'is_active' => true,
        ]);
    }

    private function createMenu(array $attributes = []): Menu
    {
        return Menu::create(array_merge([
            'name' => 'Main Menu',
            'page_id' => $this->createPage()->id,
            'position' => 1,
            'is_active' => true,
        ], $attributes));
    }

    // GET /dashboard/website/menus

    public function test_super_admin_can_view_menus_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/website/menus')
            ->assertOk();
    }

    public function test_admin_can_view_menus_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/website/menus')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_menus(): void
    {
        $this->get('/dashboard/website/menus')
            ->assertRedirect('/login');
    }

    public function test_instructor_is_forbidden_from_menus(): void
    {
        $this->actingAs($this->instructor())
            ->get('/dashboard/website/menus')
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->postJson('/dashboard/website/menus', ['name' => 'Blocked', 'page_id' => 1])
            ->assertForbidden();
    }

    // GET create/edit pages

    public function test_super_admin_can_view_menu_create_and_edit_pages(): void
    {
        $menu = $this->createMenu();

        foreach ([['create'], ["{$menu->id}/edit"]] as [$path]) {
            $this->actingAs($this->superAdmin())
                ->get("/dashboard/website/menus/{$path}")
                ->assertOk();
        }
    }

    // POST /dashboard/website/menus

    public function test_store_creates_a_menu_and_auto_assigns_position(): void
    {
        $page = $this->createPage();

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/website/menus', [
                'name' => 'First Menu',
                'page_id' => $page->id,
            ])
            ->assertRedirect('/dashboard/website/menus')
            ->assertSessionHas('success', 'Menu created successfully.');

        $first = Menu::where('name', 'First Menu')->firstOrFail();
        $this->assertSame(1, $first->position);

        $this->actingAs($this->admin())
            ->post('/dashboard/website/menus', [
                'name' => 'Second Menu',
                'page_id' => $page->id,
            ])
            ->assertRedirect('/dashboard/website/menus');

        $second = Menu::where('name', 'Second Menu')->firstOrFail();
        $this->assertSame(2, $second->position);
    }

    public function test_store_requires_an_existing_page(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/website/menus', [
                'name' => 'Ghost Menu',
                'page_id' => 99999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['page_id']);
    }

    public function test_store_requires_a_name(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/website/menus', ['page_id' => $this->createPage()->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // PUT /dashboard/website/menus/{menu}

    public function test_update_renames_a_menu(): void
    {
        $menu = $this->createMenu();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/website/menus/{$menu->id}", [
                'name' => 'Renamed Menu',
                'page_id' => $menu->page_id,
                'is_active' => false,
            ])
            ->assertRedirect('/dashboard/website/menus')
            ->assertSessionHas('success', 'Menu updated successfully.');

        $fresh = $menu->fresh();

        $this->assertSame('Renamed Menu', $fresh->name);
        $this->assertFalse($fresh->is_active);
    }

    public function test_update_refuses_itself_as_parent(): void
    {
        $menu = $this->createMenu();

        $this->actingAs($this->superAdmin())
            ->putJson("/dashboard/website/menus/{$menu->id}", [
                'name' => 'Self Parent',
                'page_id' => $menu->page_id,
                'parent_id' => $menu->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    // PATCH status

    public function test_status_toggle_updates_the_menu(): void
    {
        $menu = $this->createMenu(['is_active' => false]);

        $this->actingAs($this->admin())
            ->patch("/dashboard/website/menus/{$menu->id}/status", ['is_active' => true])
            ->assertRedirect()
            ->assertSessionHas('success', 'Menu activated successfully.');

        $this->assertTrue($menu->fresh()->is_active);
    }

    // PUT /dashboard/website/menus/reorder

    public function test_reorder_persists_new_positions(): void
    {
        $menuA = $this->createMenu(['name' => 'A', 'position' => 1]);
        $menuB = $this->createMenu(['name' => 'B', 'position' => 2]);

        $this->actingAs($this->superAdmin())
            ->put('/dashboard/website/menus/reorder', [
                'menus' => [
                    ['id' => $menuB->id, 'position' => 1],
                    ['id' => $menuA->id, 'position' => 2],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Menu order updated successfully.');

        $this->assertSame(1, $menuB->fresh()->position);
        $this->assertSame(2, $menuA->fresh()->position);
    }

    public function test_reorder_validates_payload_shape(): void
    {
        $this->actingAs($this->superAdmin())
            ->putJson('/dashboard/website/menus/reorder', ['menus' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['menus']);
    }

    // DELETE /dashboard/website/menus/{menu}

    public function test_destroy_deletes_a_menu(): void
    {
        $menu = $this->createMenu();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/website/menus/{$menu->id}")
            ->assertRedirect()
            ->assertSessionHas('success', 'Menu deleted successfully.');

        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    }
}
