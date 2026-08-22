<?php

namespace Tests\Feature\Course;

use App\Models\Category;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createCategory(array $attributes = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Electronics',
            'status' => 'active',
        ], $attributes));
    }

    // Access control: /dashboard/course/** is super_admin only + verified email

    public function test_super_admin_can_view_categories_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/course/categories')
            ->assertOk();
    }

    public function test_admin_is_forbidden_from_course_module(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/course/categories')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_categories(): void
    {
        $this->get('/dashboard/course/categories')
            ->assertRedirect('/login');
    }

    public function test_instructor_is_forbidden_from_course_module(): void
    {
        $this->actingAs($this->instructor())
            ->postJson('/dashboard/course/categories', ['name' => 'Blocked'])
            ->assertForbidden();

        $this->assertDatabaseMissing('categories', ['name' => 'Blocked']);
    }

    // GET create/edit pages

    public function test_super_admin_can_view_category_create_and_edit_pages(): void
    {
        $category = $this->createCategory();

        foreach ([['create'], ["{$category->id}/edit"]] as [$path]) {
            $this->actingAs($this->superAdmin())
                ->get("/dashboard/course/categories/{$path}")
                ->assertOk();
        }
    }

    // POST /dashboard/course/categories

    public function test_super_admin_can_create_a_category(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/dashboard/course/categories', [
                'name' => 'Networking',
                'status' => 'inactive',
            ])
            ->assertRedirect('/dashboard/course/categories')
            ->assertSessionHas('success', 'Category created successfully');

        $this->assertDatabaseHas('categories', [
            'name' => 'Networking',
            'status' => 'inactive',
        ]);
    }

    public function test_store_defaults_status_to_active(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/dashboard/course/categories', ['name' => 'Default Status'])
            ->assertRedirect('/dashboard/course/categories');

        $this->assertSame('active', Category::where('name', 'Default Status')->first()->status);
    }

    public function test_store_rejects_invalid_status_values(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/course/categories', [
                'name' => 'Bad Status',
                'status' => 'archived',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_store_requires_name(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/course/categories', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // PUT /dashboard/course/categories/{category}

    public function test_super_admin_can_update_a_category(): void
    {
        $category = $this->createCategory();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/course/categories/{$category->id}", [
                'name' => 'Renamed Category',
                'status' => 'inactive',
            ])
            ->assertRedirect('/dashboard/course/categories')
            ->assertSessionHas('success', 'Category updated successfully');

        $fresh = $category->fresh();

        $this->assertSame('Renamed Category', $fresh->name);
        $this->assertSame('inactive', $fresh->status);
    }

    // DELETE /dashboard/course/categories/{category}

    public function test_super_admin_can_delete_a_category(): void
    {
        $category = $this->createCategory();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/course/categories/{$category->id}")
            ->assertRedirect('/dashboard/course/categories')
            ->assertSessionHas('success', 'Category deleted successfully');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
