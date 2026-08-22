<?php

namespace Tests\Feature\Terms;

use App\Models\Term;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class TermControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createTerm(string $name = 'Mon & Tue'): Term
    {
        return Term::create(['term_name' => $name]);
    }

    // GET /dashboard/terms

    public function test_super_admin_can_view_terms_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/terms')
            ->assertOk();
    }

    public function test_admin_can_view_terms_index(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/terms')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_terms(): void
    {
        $this->get('/dashboard/terms')
            ->assertRedirect('/login');
    }

    public function test_instructor_cannot_view_or_manage_terms(): void
    {
        $term = $this->createTerm();

        $this->actingAs($this->instructor())
            ->get('/dashboard/terms')
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->postJson('/dashboard/terms', ['term_name' => 'Blocked'])
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->deleteJson("/dashboard/terms/{$term->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('terms', ['id' => $term->id]);
    }

    // GET create/edit pages

    public function test_super_admin_can_view_term_create_and_edit_pages(): void
    {
        $term = $this->createTerm();

        $this->actingAs($this->superAdmin())
            ->get('/dashboard/terms/create')
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->get("/dashboard/terms/{$term->id}/edit")
            ->assertOk();
    }

    // POST /dashboard/terms

    public function test_super_admin_can_create_a_term(): void
    {
        $this->actingAs($this->superAdmin())
            ->post('/dashboard/terms', ['term_name' => 'Wed & Thu'])
            ->assertRedirect('/dashboard/terms');

        $this->assertDatabaseHas('terms', ['term_name' => 'Wed & Thu']);
    }

    public function test_admin_can_create_a_term(): void
    {
        $this->actingAs($this->admin())
            ->post('/dashboard/terms', ['term_name' => 'Weekend'])
            ->assertRedirect('/dashboard/terms');

        $this->assertDatabaseHas('terms', ['term_name' => 'Weekend']);
    }

    public function test_store_requires_term_name(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/terms', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['term_name']);
    }

    public function test_store_rejects_names_over_255_characters(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/dashboard/terms', ['term_name' => str_repeat('a', 256)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['term_name']);
    }

    // PUT /dashboard/terms/{term}

    public function test_super_admin_can_update_a_term(): void
    {
        $term = $this->createTerm();

        $this->actingAs($this->superAdmin())
            ->put("/dashboard/terms/{$term->id}", ['term_name' => 'Fri Only'])
            ->assertRedirect('/dashboard/terms');

        $this->assertSame('Fri Only', $term->fresh()->term_name);
    }

    public function test_update_requires_term_name(): void
    {
        $term = $this->createTerm();

        $this->actingAs($this->admin())
            ->putJson("/dashboard/terms/{$term->id}", ['term_name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['term_name']);

        $this->assertSame('Mon & Tue', $term->fresh()->term_name);
    }

    // DELETE /dashboard/terms/{term}

    public function test_super_admin_can_delete_a_term(): void
    {
        $term = $this->createTerm();

        $this->actingAs($this->superAdmin())
            ->delete("/dashboard/terms/{$term->id}")
            ->assertRedirect('/dashboard/terms');

        $this->assertDatabaseMissing('terms', ['id' => $term->id]);
    }
}
