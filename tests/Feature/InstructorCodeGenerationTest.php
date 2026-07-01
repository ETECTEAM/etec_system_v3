<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\InstructorData;
use App\Models\User;
use App\Modules\Instructor\Services\InstructorService;

class InstructorCodeGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_code_has_etec_prefix(): void
    {
        $code = InstructorService::generateInstructorCode();

        $this->assertStringStartsWith('ETEC-', $code);
        $this->assertMatchesRegularExpression('/^ETEC-\d{3}$/', $code);
    }

    public function test_generated_code_increments_sequentially(): void
    {
        User::factory()->create();
        InstructorData::create(['instructor_code' => 'ETEC-005', 'user_id' => 1]);

        $this->assertSame('ETEC-006', InstructorService::generateInstructorCode());
    }

    public function test_generated_code_starts_at_001_when_no_existing_codes(): void
    {
        $this->assertSame('ETEC-001', InstructorService::generateInstructorCode());
    }

    public function test_instructor_service_auto_generates_code_when_syncing_instructor(): void
    {
        $user = User::factory()->create();

        $service = app(InstructorService::class);
        $service->syncProfile($user, 'instructor', [], []);

        $user->refresh();

        $this->assertNotNull($user->instructorData);
        $this->assertNotNull($user->instructorData->instructor_code);
        $this->assertStringStartsWith('ETEC-', $user->instructorData->instructor_code);
    }

    public function test_sync_profile_clears_instructor_data_for_non_instructor_roles(): void
    {
        $user = User::factory()->create();
        InstructorData::create(['user_id' => $user->id, 'instructor_code' => 'ETEC-001']);

        $service = app(InstructorService::class);
        $service->syncProfile($user, 'admin', [], []);

        $user->refresh();

        $this->assertNull($user->instructorData);
    }

    public function test_sync_profile_does_not_generate_instructor_code_when_one_already_provided(): void
    {
        $user = User::factory()->create();

        $service = app(InstructorService::class);
        $service->syncProfile($user, 'instructor', [], ['instructor_code' => 'CUSTOM-001']);

        $user->refresh();

        $this->assertNotNull($user->instructorData);
        $this->assertSame('CUSTOM-001', $user->instructorData->instructor_code);
    }
}
