<?php

namespace Tests\Feature\Attendance;

use App\Enums\UserStatus;
use App\Models\GradingSetting;
use App\Models\Time;
use App\Models\User;
use Database\Seeders\Core\RoleSeeder;
use Database\Seeders\GradingSettingSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AttendanceSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleSeeder::class);
        $this->seed(GradingSettingSeeder::class);
        Cache::forget(GradingSetting::CACHE_KEY);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_instructor_cannot_reach_the_settings_page(): void
    {
        $instructor = User::factory()->create(['status' => UserStatus::Active]);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->get('/dashboard/attendance-settings')
            ->assertForbidden();
    }

    public function test_grace_minutes_must_be_less_than_the_shortest_configured_class_duration(): void
    {
        Time::create(['time_name' => '09:00 AM - 09:20 AM']); // 20-minute class

        $response = $this->actingAs($this->superAdmin())
            ->from('/dashboard/attendance-settings')
            ->put('/dashboard/attendance-settings', [
                'auto_record_enabled' => true,
                'auto_record_grace_minutes' => 20, // equal to the shortest duration - rejected
                'auto_record_default_status' => 'present',
                'auto_record_notify_instructor' => true,
                'auto_record_allow_override' => true,
                'auto_record_override_hours' => 24,
            ]);

        $response->assertSessionHasErrors('auto_record_grace_minutes');
        $this->assertSame('15', GradingSetting::where('key', 'attendance.auto_record_grace_minutes')->value('value'));
    }

    public function test_default_status_cannot_be_set_to_absent(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->from('/dashboard/attendance-settings')
            ->put('/dashboard/attendance-settings', [
                'auto_record_enabled' => true,
                'auto_record_grace_minutes' => 15,
                'auto_record_default_status' => 'absent',
                'auto_record_notify_instructor' => true,
                'auto_record_allow_override' => true,
                'auto_record_override_hours' => 24,
            ]);

        $response->assertSessionHasErrors('auto_record_default_status');
    }

    public function test_valid_settings_save_and_take_effect_immediately(): void
    {
        Time::create(['time_name' => '09:00 AM - 10:30 AM']); // 90-minute class

        $this->actingAs($this->superAdmin())
            ->put('/dashboard/attendance-settings', [
                'auto_record_enabled' => true,
                'auto_record_grace_minutes' => 10,
                'auto_record_default_status' => 'pending',
                'auto_record_notify_instructor' => false,
                'auto_record_allow_override' => true,
                'auto_record_override_hours' => 48,
            ])
            ->assertRedirect(route('attendance-settings.edit'));

        // Cache must have been busted by the save, not just the database row.
        $this->assertSame(10, setting('attendance.auto_record_grace_minutes'));
        $this->assertSame('pending', setting('attendance.auto_record_default_status'));
        $this->assertSame(48, setting('attendance.auto_record_override_hours'));
    }
}
