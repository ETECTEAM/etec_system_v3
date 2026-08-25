<?php

namespace Tests\Unit\OfficialLeave;

use App\Models\LeaveRequestSession;
use App\Models\OfficialLeaveSetting;
use App\Modules\OfficialLeave\Services\LeaveQrService;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;
use Tests\Unit\OfficialLeave\Concerns\CreatesOfficialLeaveFixtures;

class LeaveQrServiceTest extends TestCase
{
    use CreatesOfficialLeaveFixtures;
    use RefreshDatabase;

    private LeaveQrService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // Same stale-array-cache hazard as the grading settings tests.
        Cache::forget(OfficialLeaveSetting::CACHE_KEY);

        $this->service = app(LeaveQrService::class);
    }

    public function test_create_session_stores_only_the_hash_and_returns_a_signed_url(): void
    {
        $student = $this->makeLeaveStudent();
        $admin = $this->makeAdmin();

        $result = $this->service->createSession($student, $admin);

        $session = LeaveRequestSession::query()->findOrFail($result['session_id']);

        $this->assertSame($student->id, $session->student_id);
        $this->assertSame($admin->id, $session->created_by);
        $this->assertNull($session->used_at);
        // The plain token never touches the database — only its sha256 hash.
        $this->assertDatabaseMissing('leave_request_sessions', [
            'id' => $session->id,
            'token_hash' => $result['url'],
        ]);
        $this->assertSame(15 * 60, $result['ttl_seconds']);
        $this->assertTrue(str_contains($result['url'], '/leave-request/'));
    }

    public function test_signed_url_validates_against_the_session_expiry(): void
    {
        $student = $this->makeLeaveStudent();
        $admin = $this->makeAdmin();

        $result = $this->service->createSession($student, $admin);

        $this->get($result['url'])
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('frontend/LeaveRequestForm'));
    }

    public function test_resolve_reports_each_state(): void
    {
        $student = $this->makeLeaveStudent();
        $admin = $this->makeAdmin();
        $creator = fn () => $this->service->createSession($student, $admin)['url'];

        // Valid: fresh session.
        $fresh = $creator();
        $token = $this->extractToken($fresh);
        $this->assertSame('valid', $this->service->resolve($token)['state']);

        // Already used.
        $used = $creator();
        $usedToken = $this->extractToken($used);
        LeaveRequestSession::query()
            ->where('token_hash', LeaveQrService::hash($usedToken))
            ->update(['used_at' => now()]);
        $this->assertSame('already_used', $this->service->resolve($usedToken)['state']);

        // Expired but unused.
        $expired = $creator();
        $expiredToken = $this->extractToken($expired);
        LeaveRequestSession::query()
            ->where('token_hash', LeaveQrService::hash($expiredToken))
            ->update(['expires_at' => now()->subMinute()]);
        $resolvedExpired = $this->service->resolve($expiredToken);
        $this->assertSame('expired', $resolvedExpired['state']);

        // Not found: unknown token hashes to no row.
        $this->assertSame('not_found', $this->service->resolve(str_repeat('ab', 32))['state']);
    }

    public function test_lock_for_consumption_finds_by_hash(): void
    {
        $student = $this->makeLeaveStudent();
        $admin = $this->makeAdmin();

        $result = $this->service->createSession($student, $admin);
        $token = $this->extractToken($result['url']);

        $locked = $this->service->lockForConsumption($token);

        $this->assertNotNull($locked);
        $this->assertSame($result['session_id'], $locked->id);
        $this->assertNull($this->service->lockForConsumption('missing-token'));
    }

    private function extractToken(string $signedUrl): string
    {
        // URL shape: /leave-request/{token}?expires=...&signature=...
        $path = (string) parse_url($signedUrl, PHP_URL_PATH);

        return basename($path);
    }
}
