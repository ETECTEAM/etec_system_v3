<?php

namespace Tests\Feature\OfficialLeave;

use App\Models\AuditLog;
use App\Models\LeaveRequestSession;
use App\Models\OfficialLeave;
use App\Modules\OfficialLeave\Services\AuditLogger;
use App\Modules\OfficialLeave\Services\LeaveQrService;
use App\Modules\OfficialLeave\Services\OfficialLeaveService;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tests\Unit\OfficialLeave\Concerns\CreatesOfficialLeaveFixtures;

class OfficialLeaveApprovalTest extends TestCase
{
    use CreatesOfficialLeaveFixtures;
    use RefreshDatabase;

    private OfficialLeaveService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Carbon::setTestNow(null);
        $this->service = app(OfficialLeaveService::class);
    }

    public function test_submit_from_token_creates_a_pending_leave_and_burns_the_session(): void
    {
        $student = $this->makeLeaveStudent();
        $admin = $this->makeAdmin();

        ['url' => $url] = app(LeaveQrService::class)->createSession($student, $admin);
        $token = $this->extractToken($url);

        $leave = $this->service->submitFromToken($token, [
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(2)->toDateString(),
            'reason' => '  Medical checkup  ',
        ]);

        $this->assertSame(OfficialLeave::STATUS_PENDING, $leave->status);
        $this->assertSame('Medical checkup', $leave->reason);
        $this->assertSame($student->id, $leave->student_id);

        $session = LeaveRequestSession::query()->find($leave->leave_request_session_id);
        $this->assertNotNull($session?->used_at);
    }

    public function test_a_used_token_cannot_submit_twice(): void
    {
        $student = $this->makeLeaveStudent();
        $admin = $this->makeAdmin();

        ['url' => $url] = app(LeaveQrService::class)->createSession($student, $admin);
        $token = $this->extractToken($url);

        $this->service->submitFromToken($token, [
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'reason' => 'First try',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->submitFromToken($token, [
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'reason' => 'Second try',
        ]);
    }

    public function test_an_unknown_token_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->submitFromToken(str_repeat('cd', 32), [
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'reason' => 'Ghost',
        ]);
    }

    public function test_approve_sets_decision_fields_and_writes_an_audit_row(): void
    {
        $student = $this->makeLeaveStudent();
        $admin = $this->makeAdmin();
        $leave = $this->makeLeave($student);

        $approved = $this->service->approve($admin, $leave, '203.0.113.9');

        $this->assertSame(OfficialLeave::STATUS_APPROVED, $approved->status);
        $this->assertSame($admin->id, $approved->approved_by);
        $this->assertNotNull($approved->approved_at);

        $audit = AuditLog::query()
            ->where('action', AuditLogger::ACTION_LEAVE_APPROVED)
            ->where('official_leave_id', $approved->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame('pending', $audit->before['status']);
        $this->assertSame('approved', $audit->after['status']);
        $this->assertSame('203.0.113.9', $audit->ip);
    }

    public function test_approval_fails_when_an_overlapping_approved_leave_exists(): void
    {
        $student = $this->makeLeaveStudent();
        $admin = $this->makeAdmin();

        $existing = $this->makeLeave($student, [
            'start_date' => today()->addDays(5)->toDateString(),
            'end_date' => today()->addDays(8)->toDateString(),
            'status' => OfficialLeave::STATUS_APPROVED,
        ]);
        $overlap = $this->makeLeave($student, [
            'start_date' => today()->addDays(7)->toDateString(),
            'end_date' => today()->addDays(10)->toDateString(),
        ]);

        try {
            $this->service->approve($admin, $overlap);
            $this->fail('Expected the overlapping approval to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('overlap', $exception->errors());
        }

        $this->assertSame(OfficialLeave::STATUS_PENDING, $overlap->fresh()->status);
        // The untouched approved leave stays approved.
        $this->assertSame(OfficialLeave::STATUS_APPROVED, $existing->fresh()->status);
    }

    public function test_only_pending_leaves_can_be_rejected_with_a_note(): void
    {
        $student = $this->makeLeaveStudent();
        $admin = $this->makeAdmin();
        $leave = $this->makeLeave($student);

        $rejected = $this->service->reject($admin, $leave, 'Dates unclear');

        $this->assertSame(OfficialLeave::STATUS_REJECTED, $rejected->status);
        $this->assertSame($admin->id, $rejected->rejected_by);
        $this->assertSame('Dates unclear', $rejected->rejection_note);

        // A second reject attempt hits the status guard.
        $this->expectException(ValidationException::class);
        $this->service->reject($admin, $rejected, 'Again');
    }

    public function test_revocation_rules_by_role_and_start_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 10:00'));

        $student = $this->makeLeaveStudent();
        $admin = $this->makeAdmin();
        $superAdmin = $this->makeSuperAdmin();

        // Not started yet: an admin may revoke.
        $upcoming = $this->makeLeave($student, [
            'start_date' => '2026-08-22',
            'end_date' => '2026-08-24',
            'status' => OfficialLeave::STATUS_APPROVED,
        ]);
        $revokedByAdmin = $this->service->revoke($admin, $upcoming, 'Student returned early');

        $this->assertSame(OfficialLeave::STATUS_REVOKED, $revokedByAdmin->status);
        $this->assertSame($admin->id, $revokedByAdmin->revoked_by);
        $this->assertSame('Student returned early', $revokedByAdmin->revoked_note);

        // Already started: only super_admin may revoke.
        $started = $this->makeLeave($student, [
            'start_date' => '2026-08-19',
            'end_date' => '2026-08-21',
            'status' => OfficialLeave::STATUS_APPROVED,
        ]);

        try {
            $this->service->revoke($admin, $started);
            $this->fail('Expected the started leave to be admin-revocable.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('super admin', $exception->errors()['status'][0]);
        }

        $revokedBySuper = $this->service->revoke($superAdmin, $started);

        $this->assertSame(OfficialLeave::STATUS_REVOKED, $revokedBySuper->status);
        $this->assertSame($superAdmin->id, $revokedBySuper->revoked_by);
    }

    public function test_delete_soft_deletes_and_keeps_the_audit_trail(): void
    {
        $student = $this->makeLeaveStudent();
        $superAdmin = $this->makeSuperAdmin();
        $leave = $this->makeLeave($student, ['status' => OfficialLeave::STATUS_APPROVED]);

        $this->service->delete($superAdmin, $leave);

        $this->assertSoftDeleted('official_leaves', ['id' => $leave->id]);

        $audit = AuditLog::query()->where('action', AuditLogger::ACTION_LEAVE_DELETED)->first();
        $this->assertNotNull($audit);
        $this->assertSame($superAdmin->id, $audit->user_id);
        $this->assertNull($audit->official_leave_id);
        $this->assertSame('approved', $audit->before['status']);
    }

    public function test_is_on_approved_leave_checks_status_and_range(): void
    {
        $student = $this->makeLeaveStudent();
        $pendingOwner = $this->makeLeaveStudent();
        $today = today();

        $this->makeLeave($student, [
            'start_date' => $today->copy()->subDay()->toDateString(),
            'end_date' => $today->copy()->addDay()->toDateString(),
            'status' => OfficialLeave::STATUS_APPROVED,
        ]);

        $this->assertTrue($this->service->isOnApprovedLeave($student->id));
        $this->assertTrue($this->service->isOnApprovedLeave($student->id, $today->toDateString()));
        $this->assertFalse($this->service->isOnApprovedLeave($student->id, $today->copy()->addDays(3)->toDateString()));

        // A pending leave doesn't count as on-leave...
        $pending = $this->makeLeave($pendingOwner, [
            'start_date' => $today->toDateString(),
            'end_date' => $today->toDateString(),
        ]);
        $this->assertFalse($this->service->isOnApprovedLeave($pendingOwner->id));

        // ...until approved.
        $this->service->approve($this->makeSuperAdmin(), $pending);
        $this->assertTrue($this->service->isOnApprovedLeave($pendingOwner->id));
    }

    private function extractToken(string $signedUrl): string
    {
        // URL shape: /leave-request/{token}?expires=...&signature=...
        $path = (string) parse_url($signedUrl, PHP_URL_PATH);

        return basename($path);
    }
}
