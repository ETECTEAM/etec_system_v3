<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use App\Modules\Auth\Services\AuthAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_records_the_action_with_metadata_and_actor(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create();

        $entry = app(AuthAuditService::class)->log($user, 'user.approved', '10.0.0.1', ['source' => 'telegram'], $actor->id);

        $this->assertDatabaseHas('auth_audit_logs', [
            'id' => $entry->id,
            'user_id' => $user->id,
            'action' => 'user.approved',
            'ip_address' => '10.0.0.1',
            'created_by' => $actor->id,
        ]);
        $this->assertSame(['source' => 'telegram'], $entry->fresh()->metadata);
    }

    public function test_log_accepts_a_null_user_for_anonymous_events(): void
    {
        $entry = app(AuthAuditService::class)->log(null, 'login.failed', '10.0.0.2');

        $this->assertNull($entry->user_id);
        $this->assertSame('login.failed', $entry->action);
    }
}
