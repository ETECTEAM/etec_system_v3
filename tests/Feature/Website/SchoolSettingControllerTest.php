<?php

namespace Tests\Feature\Website;

use App\Models\SchoolSetting;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class SchoolSettingControllerTest extends TestCase
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

    // GET /dashboard/website/school-settings

    public function test_super_admin_can_view_school_settings(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/website/school-settings')
            ->assertOk();

        $this->assertSame(1, SchoolSetting::query()->count());
    }

    public function test_admin_can_view_school_settings(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/website/school-settings')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_school_settings(): void
    {
        $this->get('/dashboard/website/school-settings')
            ->assertRedirect('/login');
    }

    public function test_instructor_is_forbidden_from_school_settings(): void
    {
        $this->actingAs($this->instructor())
            ->get('/dashboard/website/school-settings')
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->putJson('/dashboard/website/school-settings', ['school_name' => 'Blocked'])
            ->assertForbidden();
    }

    // PUT /dashboard/website/school-settings

    public function test_update_changes_the_school_name(): void
    {
        $this->actingAs($this->superAdmin())
            ->put('/dashboard/website/school-settings', [
                'school_name' => 'Renamed Center',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'School settings updated successfully.');

        $this->assertSame(1, SchoolSetting::where('school_name', 'Renamed Center')->count());
    }

    public function test_update_requires_a_school_name(): void
    {
        $this->actingAs($this->admin())
            ->putJson('/dashboard/website/school-settings', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['school_name']);
    }

    public function test_update_uploads_a_new_logo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin())
            ->put('/dashboard/website/school-settings', [
                'school_name' => 'Logo School',
                'school_logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $setting = SchoolSetting::firstOrFail();

        $this->assertNotNull($setting->school_logo);
        $this->assertStringStartsWith('uploads/settings/', $setting->school_logo);
        Storage::disk('public')->assertExists($setting->school_logo);
    }

    // DELETE /dashboard/website/school-settings/logo

    public function test_remove_logo_clears_the_path_and_deletes_the_file(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('old-logo.png')->store('uploads/settings', 'public');

        SchoolSetting::firstOrCreate([], ['school_name' => 'Old Name']);
        SchoolSetting::query()->update(['school_logo' => $path]);

        $this->actingAs($this->superAdmin())
            ->delete('/dashboard/website/school-settings/logo')
            ->assertRedirect()
            ->assertSessionHas('success', 'School logo removed successfully.');

        $setting = SchoolSetting::firstOrFail();

        $this->assertNull($setting->school_logo);
        Storage::disk('public')->assertMissing($path);
    }
}
