<?php

namespace Tests\Feature\Course;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class SubCategoryControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createCategory(): Category
    {
        return Category::create(['name' => 'Electronics', 'status' => 'active']);
    }

    private function createSubCategory(array $attributes = []): SubCategory
    {
        return SubCategory::create(array_merge([
            'category_id' => $this->createCategory()->id,
            'name' => 'Microcontrollers',
            'slug' => 'microcontrollers',
            'status' => 'active',
        ], $attributes));
    }

    // GET /dashboard/course/subcategories

    public function test_super_admin_can_view_subcategories_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/course/subcategories')
            ->assertOk();
    }

    public function test_admin_is_forbidden_from_subcategories(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/course/subcategories')
            ->assertForbidden();
    }

    // GET create/edit pages

    public function test_super_admin_can_view_subcategory_create_and_edit_pages(): void
    {
        $subCategory = $this->createSubCategory();

        foreach ([['create'], ["{$subCategory->id}/edit"]] as [$path]) {
            $this->actingAs($this->superAdmin())
                ->get("/dashboard/course/subcategories/{$path}")
                ->assertOk();
        }
    }

    // POST /dashboard/course/subcategories

    public function test_super_admin_can_create_a_sub_category(): void
    {
        $category = $this->createCategory();

        $this->actingAs($this->superAdmin())
            ->post('/dashboard/course/subcategories', [
                'category_id' => $category->id,
                'name' => 'Sensors',
                'status' => 'inactive',
            ])
            ->assertRedirect('/dashboard/course/subcategories')
            ->assertSessionHas('success', 'Sub Category created successfully');

        $sub = SubCategory::where('name', 'Sensors')->firstOrFail();

        $this->assertSame($category->id, $sub->category_id);
        $this->assertSame('sensors', $sub->slug);
        $this->assertSame('inactive', $sub->status);
    }

    public function test_store_requires_existing_category(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/course/subcategories', [
                'category_id' => 99999,
                'name' => 'Orphan',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_store_requires_name(): void
    {
        $category = $this->createCategory();

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/course/subcategories', [
                'category_id' => $category->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // PUT /dashboard/course/subcategories/{subCategory}

    public function test_super_admin_can_update_a_sub_category(): void
    {
        $sub = $this->createSubCategory();
        $otherCategory = Category::create(['name' => 'Mechanical', 'status' => 'active']);

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/course/subcategories/{$sub->id}", [
                'category_id' => $otherCategory->id,
                'name' => 'Actuators',
                'status' => 'inactive',
            ])
            ->assertRedirect('/dashboard/course/subcategories')
            ->assertSessionHas('success', 'Sub Category updated successfully');

        $fresh = $sub->fresh();

        $this->assertSame($otherCategory->id, $fresh->category_id);
        $this->assertSame('Actuators', $fresh->name);
        $this->assertSame('actuators', $fresh->slug);
        $this->assertSame('inactive', $fresh->status);
    }

    // DELETE /dashboard/course/subcategories/{subCategory}

    public function test_super_admin_can_delete_a_sub_category(): void
    {
        $sub = $this->createSubCategory();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/course/subcategories/{$sub->id}")
            ->assertRedirect('/dashboard/course/subcategories')
            ->assertSessionHas('success', 'Sub Category deleted successfully');

        $this->assertDatabaseMissing('sub_categories', ['id' => $sub->id]);
    }
}
