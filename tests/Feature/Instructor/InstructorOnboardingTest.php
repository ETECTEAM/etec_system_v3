<?php

namespace Tests\Feature\Instructor;

use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\InstructorData;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Modules\Instructor\Services\InstructorOnboardingService;
use Database\Seeders\Core\AssignPermissionSeeder;
use Database\Seeders\Core\PermissionSeeder;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class InstructorOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed([PermissionSeeder::class, RoleSeeder::class, AssignPermissionSeeder::class]);
    }

    private function makeInstructor(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'status' => UserStatus::Active,
            'requires_onboarding' => true,
        ], $overrides));
        $user->assignRole('instructor');

        return $user;
    }

    private static int $scheduleCounter = 0;
    private static int $instructorCounter = 0;

    private function fillProfileFields(User $user, array $overrides = []): InstructorData
    {
        self::$scheduleCounter++;
        self::$instructorCounter++;
        $schedule = WorkSchedule::create([
            'name' => 'Schedule '.self::$scheduleCounter,
            'code' => 'part_time_weekend_morning_'.self::$scheduleCounter,
            'is_active' => true,
        ]);

        return InstructorData::create(array_merge([
            'user_id' => $user->id,
            'full_name' => 'Bopha Kem',
            'instructor_code' => 'ETEC-'.self::$instructorCounter,
            'employment_type' => 'part_time',
            'work_schedule_id' => $schedule->id,
            'specialization' => ['Electronics'],
            'status' => true,
        ], $overrides));
    }

    public function test_service_marks_user_pending_until_all_required_setup_is_complete(): void
    {
        $service = app(InstructorOnboardingService::class);

        // No profile fields yet - still pending.
        $fresh = $this->makeInstructor();
        $this->assertTrue($service->isPending($fresh));

        // Profile complete but no verified recovery email - still pending.
        $completeProfile = $this->makeInstructor();
        $this->fillProfileFields($completeProfile);
        $this->assertTrue($service->isPending($completeProfile));

        // Recovery email set + verified and profile complete - done.
        $done = $this->makeInstructor();
        $this->fillProfileFields($done);
        $done->forceFill(['recovery_email' => 'recovery@example.com', 'recovery_verified' => true])->save();
        $this->assertFalse($service->isPending($done));
        $this->assertTrue($service->isComplete($done));
    }

    public function test_existing_instructors_are_not_gated(): void
    {
        $service = app(InstructorOnboardingService::class);
        $existing = $this->makeInstructor(['requires_onboarding' => false]);

        $this->assertFalse($service->isPending($existing));
    }

    public function test_pending_onboarding_instructor_is_redirected_to_the_wizard(): void
    {
        $user = $this->makeInstructor();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/dashboard/instructor/onboarding');
    }

    public function test_pending_onboarding_instructor_can_still_reach_profile_page(): void
    {
        $user = $this->makeInstructor();
        $this->fillProfileFields($user);

        $this->actingAs($user)
            ->get('/dashboard/instructor/profile')
            ->assertOk();
    }

    public function test_pending_onboarding_instructor_can_reach_the_wizard(): void
    {
        $user = $this->makeInstructor();

        $this->actingAs($user)
            ->get('/dashboard/instructor/onboarding')
            ->assertOk();
    }

    public function test_completed_instructor_visiting_the_wizard_is_sent_to_dashboard(): void
    {
        $user = $this->makeInstructor();
        $this->fillProfileFields($user);
        $user->forceFill([
            'recovery_email' => 'recovery@example.com',
            'recovery_verified' => true,
            'onboarding_completed_at' => now(),
        ])->save();

        $this->actingAs($user)
            ->get('/dashboard/instructor/onboarding')
            ->assertRedirect('/dashboard');
    }

    public function test_teaching_step_persists_the_required_fields(): void
    {
        $user = $this->makeInstructor();
        InstructorData::create([
            'user_id' => $user->id,
            'full_name' => 'Bopha Kem',
            'instructor_code' => 'ETEC-'.uniqid(),
            'status' => true,
        ]);

        $category = Category::create(['name' => 'Technical', 'status' => 'active']);
        SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);
        $schedule = WorkSchedule::create([
            'name' => 'Weekend Morning',
            'code' => 'part_time_weekend_morning_'.uniqid(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put('/dashboard/instructor/onboarding/teaching', [
                'employment_type' => 'part_time',
                'work_schedule_id' => (string) $schedule->id,
                'specialization' => ['Electronics'],
            ])
            ->assertRedirect('/dashboard/instructor/onboarding');

        $instructor = $user->instructorData()->first();
        $this->assertSame('part_time', $instructor->employment_type);
        $this->assertSame($schedule->id, $instructor->work_schedule_id);
        $this->assertSame(['Electronics'], $instructor->specialization);
    }

    public function test_teaching_step_completes_onboarding_when_recovery_is_already_verified(): void
    {
        $user = $this->makeInstructor();
        InstructorData::create([
            'user_id' => $user->id,
            'full_name' => 'Bopha Kem',
            'instructor_code' => 'ETEC-'.uniqid(),
            'status' => true,
        ]);
        $user->forceFill(['recovery_email' => 'recovery@example.com', 'recovery_verified' => true])->save();

        $category = Category::create(['name' => 'Technical', 'status' => 'active']);
        SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);
        $schedule = WorkSchedule::create([
            'name' => 'Weekend Morning',
            'code' => 'part_time_weekend_morning_'.uniqid(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put('/dashboard/instructor/onboarding/teaching', [
                'employment_type' => 'part_time',
                'work_schedule_id' => (string) $schedule->id,
                'specialization' => ['Electronics'],
            ]);

        $this->assertNotNull($user->refresh()->onboarding_completed_at);
    }

    public function test_status_endpoint_reports_recovery_and_completion_state(): void
    {
        $user = $this->makeInstructor();
        $this->fillProfileFields($user);

        $this->actingAs($user)
            ->getJson('/dashboard/instructor/onboarding/status')
            ->assertOk()
            ->assertJson(['recoveryVerified' => false, 'complete' => false]);

        $user->forceFill(['recovery_email' => 'recovery@example.com', 'recovery_verified' => true])->save();

        $this->actingAs($user)
            ->getJson('/dashboard/instructor/onboarding/status')
            ->assertOk()
            ->assertJson(['recoveryVerified' => true, 'complete' => true]);
    }

    public function test_completed_onboarding_instructor_can_reach_dashboard(): void
    {
        $user = $this->makeInstructor();
        $this->fillProfileFields($user);
        $user->forceFill(['recovery_email' => 'recovery@example.com', 'recovery_verified' => true])->save();
        $user->forceFill(['onboarding_completed_at' => now()])->save();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_saving_complete_profile_marks_onboarding_complete(): void
    {
        $user = $this->makeInstructor();

        // The specialization must be a valid active sub-category for the
        // request to pass validation.
        $category = Category::create(['name' => 'Technical', 'status' => 'active']);
        SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);

        $schedule = WorkSchedule::create([
            'name' => 'Weekend Morning',
            'code' => 'part_time_weekend_morning_'.uniqid(),
            'is_active' => true,
        ]);

        // Start with an incomplete profile (employment type + specialization
        // present, but no work schedule yet) and no verified recovery email.
        $instructor = InstructorData::create([
            'user_id' => $user->id,
            'full_name' => 'Bopha Kem',
            'instructor_code' => 'ETEC-'.uniqid(),
            'employment_type' => 'part_time',
            'specialization' => ['Electronics'],
            'status' => true,
        ]);

        // Complete the required setup: pick a work schedule and verify recovery email.
        $user->forceFill(['recovery_email' => 'recovery@example.com', 'recovery_verified' => true])->save();

        $this->actingAs($user)
            ->post('/dashboard/instructor/profile', [
                '_method' => 'put',
                'email' => $user->email,
                'full_name' => 'Bopha Kem',
                'instructor_code' => $instructor->instructor_code,
                'phone' => '',
                'employment_type' => 'part_time',
                'specialization' => ['Electronics'],
                'work_schedule_id' => (string) $schedule->id,
                'headline' => '',
                'bio' => '',
                'date_of_birth' => '',
                'gender' => '',
                'address' => '',
                'telegram' => '',
                'linkedin' => '',
                'github' => '',
                'portfolio_url' => '',
                'password' => '',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->onboarding_completed_at);
    }

    public function test_saving_a_complete_profile_with_no_recovery_email_sends_the_user_to_the_wizard(): void
    {
        $user = $this->makeInstructor();

        $category = Category::create(['name' => 'Technical', 'status' => 'active']);
        SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
        ]);
        $schedule = WorkSchedule::create([
            'name' => 'Weekend Morning',
            'code' => 'part_time_weekend_morning_'.uniqid(),
            'is_active' => true,
        ]);
        $instructor = InstructorData::create([
            'user_id' => $user->id,
            'full_name' => 'Bopha Kem',
            'instructor_code' => 'ETEC-'.uniqid(),
            'status' => true,
        ]);

        $this->actingAs($user)
            ->post('/dashboard/instructor/profile', [
                '_method' => 'put',
                'email' => $user->email,
                'full_name' => 'Bopha Kem',
                'instructor_code' => $instructor->instructor_code,
                'phone' => '',
                'employment_type' => 'part_time',
                'specialization' => ['Electronics'],
                'work_schedule_id' => (string) $schedule->id,
                'headline' => '',
                'bio' => '',
                'date_of_birth' => '',
                'gender' => '',
                'address' => '',
                'telegram' => '',
                'linkedin' => '',
                'github' => '',
                'portfolio_url' => '',
                'password' => '',
            ])
            ->assertRedirect('/dashboard/instructor/onboarding');

        $this->assertNull($user->refresh()->onboarding_completed_at);
    }

    public function test_recovery_email_verification_marks_onboarding_complete_when_profile_is_done(): void
    {
        $user = $this->makeInstructor();
        $this->fillProfileFields($user);
        // Profile already has the required fields; recovery email is the last step.
        $user->forceFill(['recovery_email' => 'recovery@example.com'])->save();

        $url = URL::temporarySignedRoute(
            'account-security.recovery-email.verify',
            now()->addHours(24),
            ['user' => $user->id]
        );

        // Clicking the link in the same session sends the instructor back to the
        // wizard, not the standalone Account Security page.
        $this->actingAs($user)
            ->get($url)
            ->assertRedirect('/dashboard/instructor/onboarding');

        $user->refresh();
        $this->assertNotNull($user->onboarding_completed_at);

        // Following that redirect shows the wizard's "all set" screen once,
        // instead of bouncing straight to the dashboard.
        $this->actingAs($user)
            ->withSession(['onboarding_just_completed' => true])
            ->get('/dashboard/instructor/onboarding')
            ->assertOk();
    }

    public function test_recovery_email_verification_outside_onboarding_returns_to_account_security(): void
    {
        $user = $this->makeInstructor(['requires_onboarding' => false]);
        $user->forceFill(['recovery_email' => 'recovery@example.com'])->save();

        $url = URL::temporarySignedRoute(
            'account-security.recovery-email.verify',
            now()->addHours(24),
            ['user' => $user->id]
        );

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('account-security.edit'));
    }

    public function test_service_requires_specialization_work_schedule_employment_type_and_recovery(): void
    {
        $service = app(InstructorOnboardingService::class);

        // Missing work schedule.
        $noSchedule = $this->makeInstructor();
        $noScheduleInstructor = $this->fillProfileFields($noSchedule, [
            // Keep the same schedule row but clear the reference on the model.
        ]);
        $noScheduleInstructor->forceFill(['work_schedule_id' => null])->save();
        $noSchedule->forceFill(['recovery_email' => 'recovery@example.com', 'recovery_verified' => true])->save();
        $this->assertFalse($service->isComplete($noSchedule));

        // Missing specialization.
        $noSpecialization = $this->makeInstructor();
        $this->fillProfileFields($noSpecialization, ['specialization' => []]);
        $noSpecialization->forceFill(['recovery_email' => 'recovery@example.com', 'recovery_verified' => true])->save();
        $this->assertFalse($service->isComplete($noSpecialization));

        // Missing employment type.
        $noEmployment = $this->makeInstructor();
        $this->fillProfileFields($noEmployment, ['employment_type' => null]);
        $noEmployment->forceFill(['recovery_email' => 'recovery@example.com', 'recovery_verified' => true])->save();
        $this->assertFalse($service->isComplete($noEmployment));
    }
}
