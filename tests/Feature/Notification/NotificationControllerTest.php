<?php

namespace Tests\Feature\Notification;

use App\Enums\UserStatus;
use App\Models\Notification;
use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\CreatesDashboardUsers;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesDashboardUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seedRoles();
    }

    private function createNotification(array $attributes = []): Notification
    {
        return Notification::create(array_merge([
            'title' => 'Instructor registration',
            'message' => 'A new instructor is waiting for approval.',
            'type' => 'instructor_approval',
            'is_read' => false,
        ], $attributes));
    }

    private function createPendingRegistrationNotification(): array
    {
        $applicant = User::factory()->create(['status' => UserStatus::Pending]);
        $applicant->assignRole('instructor');

        $otp = OtpVerification::create([
            'user_id' => $applicant->id,
            'otp_code' => '123456',
            'expires_at' => now()->addMinutes(10),
        ]);

        $notification = $this->createNotification(['otp_verification_id' => $otp->id]);

        return [$applicant, $otp, $notification];
    }

    // GET /dashboard/notifications

    public function test_super_admin_can_view_the_notifications_page(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/dashboard/notifications')
            ->assertOk();
    }

    public function test_admin_can_view_the_notifications_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/notifications')
            ->assertOk();
    }

    public function test_guest_is_redirected_from_notifications(): void
    {
        $this->get('/dashboard/notifications')
            ->assertRedirect('/login');
    }

    public function test_instructor_is_forbidden_from_notifications(): void
    {
        $this->actingAs($this->instructor())
            ->get('/dashboard/notifications')
            ->assertForbidden();

        $this->actingAs($this->instructor())
            ->getJson('/notifications/data')
            ->assertForbidden();
    }

    // GET /notifications/data

    public function test_data_endpoint_returns_unread_count_and_latest_items(): void
    {
        $read = $this->createNotification(['title' => 'Already read', 'is_read' => true]);
        $unread = $this->createNotification(['title' => 'Still unread']);

        $response = $this->actingAs($this->admin())
            ->getJson('/notifications/data')
            ->assertOk()
            ->assertJsonStructure(['unread_count', 'data']);

        $this->assertSame(1, $response->json('unread_count'));

        $titles = collect($response->json('data'))->pluck('title')->all();

        $this->assertEqualsCanonicalizing(['Already read', 'Still unread'], $titles);
    }

    // POST /notifications/{notification}/approve

    public function test_approve_activates_the_linked_pending_user(): void
    {
        Event::fake();
        [$applicant, , $notification] = $this->createPendingRegistrationNotification();

        $this->actingAs($this->superAdmin())
            ->postJson("/notifications/{$notification->id}/approve")
            ->assertOk()
            ->assertJsonPath('approval_status', 'approved');

        $this->assertSame(UserStatus::Active, $applicant->fresh()->status);
        $this->assertTrue((bool) $notification->fresh()->is_read);
    }

    // POST /notifications/{notification}/reject

    public function test_reject_rejects_the_linked_pending_user(): void
    {
        Event::fake();
        [$applicant, , $notification] = $this->createPendingRegistrationNotification();

        $this->actingAs($this->admin())
            ->postJson("/notifications/{$notification->id}/reject")
            ->assertOk()
            ->assertJsonPath('approval_status', 'rejected');

        $this->assertSame(UserStatus::Rejected, $applicant->fresh()->status);
    }

    public function test_approve_fails_for_a_notification_without_a_linkable_user(): void
    {
        $orphan = $this->createNotification();

        $this->actingAs($this->superAdmin())
            ->postJson("/notifications/{$orphan->id}/approve")
            ->assertStatus(422);

        // A non-approval notification type is equally unactionable.
        $generic = $this->createNotification(['type' => 'system_alert']);
        $genericOtp = OtpVerification::create([
            'user_id' => User::factory()->create()->id,
            'otp_code' => '654321',
            'expires_at' => now()->addMinutes(10),
        ]);
        $generic->update(['otp_verification_id' => $genericOtp->id]);

        $this->actingAs($this->superAdmin())
            ->postJson("/notifications/{$generic->id}/approve")
            ->assertStatus(422);
    }

    // POST /notifications/{notification}/read

    public function test_mark_read_flags_a_single_notification(): void
    {
        $notification = $this->createNotification();

        Event::fake();

        $this->actingAs($this->admin())
            ->postJson("/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('is_read', true);

        $this->assertTrue((bool) $notification->fresh()->is_read);
    }

    // POST /notifications/mark-all-read

    public function test_mark_all_read_clears_every_unread_notification(): void
    {
        $this->createNotification();
        $this->createNotification();

        Event::fake();

        $this->actingAs($this->superAdmin())
            ->postJson('/notifications/mark-all-read')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, Notification::where('is_read', false)->count());
    }

    // DELETE /notifications/{notification}

    public function test_destroy_deletes_a_notification(): void
    {
        $notification = $this->createNotification();

        Event::fake();

        $this->actingAs($this->superAdmin())
            ->deleteJson("/notifications/{$notification->id}")
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertDatabaseMissing('dashboard_notifications', ['id' => $notification->id]);
    }
}
