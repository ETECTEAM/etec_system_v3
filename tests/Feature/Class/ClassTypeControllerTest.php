<?php

namespace Tests\Feature\Class;

use App\Models\ClassType;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class ClassTypeControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createClassType(array $attributes = []): ClassType
    {
        return ClassType::create(array_merge([
            'type_name' => 'Network',
            'description' => 'Networking classes',
            'is_active' => true,
        ], $attributes));
    }

    // GET /dashboard/class-types

    public function test_super_admin_can_view_class_types_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/class-types')
            ->assertOk();
    }

    public function test_admin_can_view_class_types_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/class-types')
            ->assertOk();
    }

    public function test_admin_can_get_paginated_class_types_for_inline_editing(): void
    {
        $this->createClassType(['type_name' => 'Inline Type']);

        $this->actingAs($this->admin())
            ->getJson('/dashboard/class-types/data?search=Inline')
            ->assertOk()
            ->assertJsonPath('data.0.type_name', 'Inline Type');
    }

    public function test_guest_is_redirected_from_class_types(): void
    {
        $this->get('/dashboard/class-types')
            ->assertRedirect('/login');
    }

    public function test_instructor_cannot_view_or_manage_class_types(): void
    {
        $classType = $this->createClassType();

        $this->actingAs($this->instructor())
            ->get('/dashboard/class-types')
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->postJson('/dashboard/class-types', ['type_name' => 'Blocked'])
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->deleteJson("/dashboard/class-types/{$classType->class_type_id}")
            ->assertForbidden();

        $this->assertDatabaseHas('class_type', ['class_type_id' => $classType->class_type_id]);
    }

    // GET create/show/edit pages

    public function test_super_admin_can_view_create_show_and_edit_pages(): void
    {
        $classType = $this->createClassType();
        $id = $classType->class_type_id;

        foreach (['create', $id, "{$id}/edit"] as $path) {
            $this->actingAs($this->superAdmin())
                ->get("/dashboard/class-types/{$path}")
                ->assertOk();
        }
    }

    // POST /dashboard/class-types

    public function test_super_admin_can_create_a_class_type(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/dashboard/class-types', [
                'type_name' => 'Programming',
                'description' => 'Coding courses',
                'is_active' => true,
            ])
            ->assertRedirect('/dashboard/class-types')
            ->assertSessionHas('success', 'Class type created successfully.');

        $classType = ClassType::where('type_name', 'Programming')->firstOrFail();

        $this->assertSame('Coding courses', $classType->description);
        $this->assertTrue($classType->is_active);
    }

    public function test_store_defaults_is_active_to_false_when_omitted(): void
    {
        $this->actingAs($this->admin())
            ->post('/dashboard/class-types', ['type_name' => 'Inactive By Default'])
            ->assertRedirect('/dashboard/class-types');

        $this->assertFalse(ClassType::where('type_name', 'Inactive By Default')->first()->is_active);
    }

    public function test_store_rejects_duplicate_type_names(): void
    {
        $this->createClassType(['type_name' => 'Unique Type']);

        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/class-types', ['type_name' => 'Unique Type'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type_name']);
    }

    public function test_store_validates_required_type_name(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/class-types', ['description' => 'No name'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type_name']);
    }

    // PUT /dashboard/class-types/{id}

    public function test_super_admin_can_update_a_class_type(): void
    {
        $classType = $this->createClassType();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/class-types/{$classType->class_type_id}", [
                'type_name' => 'Renamed Type',
                'description' => 'Updated',
                'is_active' => false,
            ])
            ->assertRedirect('/dashboard/class-types')
            ->assertSessionHas('success', 'Class type updated successfully.');

        $fresh = $classType->fresh();

        $this->assertSame('Renamed Type', $fresh->type_name);
        $this->assertSame('Updated', $fresh->description);
        $this->assertFalse($fresh->is_active);
    }

    public function test_update_returns_the_updated_class_type_for_inline_editing(): void
    {
        $classType = $this->createClassType();

        $this->actingAs($this->admin())
            ->putJson("/dashboard/class-types/{$classType->class_type_id}", [
                'type_name' => 'Inline Updated',
                'description' => 'Updated in the table',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.type_name', 'Inline Updated')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_update_allows_keeping_own_name_but_not_others(): void
    {
        $first = $this->createClassType(['type_name' => 'First']);
        $second = $this->createClassType(['type_name' => 'Second']);

        $this->actingAs($this->admin())
            ->putJson("/dashboard/class-types/{$first->class_type_id}", ['type_name' => 'First'])
            ->assertOk()
            ->assertJsonPath('data.type_name', 'First');

        $this->actingAs($this->admin())
            ->putJson("/dashboard/class-types/{$second->class_type_id}", ['type_name' => 'First'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type_name']);
    }

    public function test_update_on_missing_class_type_returns_404(): void
    {
        $this->actingAs($this->superAdmin())
            ->putJson('/dashboard/class-types/99999', ['type_name' => 'Ghost'])
            ->assertNotFound();
    }

    // DELETE /dashboard/class-types/{id}

    public function test_super_admin_can_delete_a_class_type(): void
    {
        $classType = $this->createClassType();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/class-types/{$classType->class_type_id}")
            ->assertRedirect('/dashboard/class-types')
            ->assertSessionHas('success', 'Class type deleted successfully.');

        $this->assertDatabaseMissing('class_type', ['class_type_id' => $classType->class_type_id]);
    }
}
