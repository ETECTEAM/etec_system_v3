<?php

namespace Tests\Feature\AbsenceBlock;

use App\Enums\UserStatus;
use App\Models\AttendanceRule;
use App\Models\AttendanceRuleSetting;
use App\Models\StudentAttendanceBlock;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\AbsenceBlock\Concerns\BuildsAbsenceScenario;
use Tests\TestCase;

class AbsenceBlockRoutesTest extends TestCase
{
    use RefreshDatabase;
    use BuildsAbsenceScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed([
            \Database\Seeders\Core\PermissionSeeder::class,
            \Database\Seeders\Core\RoleSeeder::class,
            \Database\Seeders\Core\AssignPermissionSeeder::class,
        ]);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['status' => UserStatus::Active]);
        $u->assignRole($role);

        return $u;
    }

    private function openBlock(string $type = StudentAttendanceBlock::TYPE_ABSENCE): StudentAttendanceBlock
    {
        $course = $this->makeCourseNamed('C');
        $student = $this->studentWithPhone('011777888');

        return StudentAttendanceBlock::create([
            'student_id' => $student->id,
            'student_tel' => $student->phone,
            'course_id' => $course->id,
            'block_type' => $type,
            'is_approved' => false,
            'blocked_at' => now(),
            'admin_comment' => $type === StudentAttendanceBlock::TYPE_HARD_LOCK ? StudentAttendanceBlock::COMMENT_HARD_LOCK : null,
            'cycle_start_date' => '2026-04-01',
        ]);
    }

    public function test_office_pages_load_for_admin(): void
    {
        $this->actingAs($this->user('admin'));

        $this->get('/dashboard/absence-blocks')->assertOk();
        $this->get('/dashboard/absence-blocks/rules')->assertOk();
        $this->get('/dashboard/absence-blocks/settings')->assertOk();
        $this->get('/dashboard/absence-blocks/audit')->assertOk();
        $this->getJson('/dashboard/absence-blocks/data')->assertOk();
    }

    public function test_non_office_roles_are_blocked(): void
    {
        $this->actingAs($this->user('instructor'));
        $this->get('/dashboard/absence-blocks')->assertForbidden();

        $this->actingAs($this->user('student'));
        $this->get('/dashboard/absence-blocks')->assertForbidden();
    }

    public function test_admin_can_crud_and_toggle_rules(): void
    {
        $this->actingAs($this->user('admin'));

        $this->post('/dashboard/absence-blocks/rules', [
            'rule_type' => 'absence', 'limit_count' => 4, 'period_type' => 'both',
            'start_date' => '2026-04-01', 'is_active' => true,
        ])->assertRedirect();

        $rule = AttendanceRule::firstOrFail();
        $this->put("/dashboard/absence-blocks/rules/{$rule->id}", [
            'rule_type' => 'absence', 'limit_count' => 6, 'period_type' => 'week',
            'start_date' => '2026-04-01', 'is_active' => true,
        ])->assertRedirect();
        $this->assertSame(6, $rule->fresh()->limit_count);

        $this->patch("/dashboard/absence-blocks/rules/{$rule->id}/toggle")->assertRedirect();
        $this->assertFalse($rule->fresh()->is_active);

        $this->delete("/dashboard/absence-blocks/rules/{$rule->id}")->assertRedirect();
        $this->assertDatabaseMissing('attendance_rules', ['id' => $rule->id]);
    }

    public function test_admin_can_approve_and_reject_absence_blocks_but_not_unlock_hard_locks(): void
    {
        $this->actingAs($this->user('admin'));

        $absence = $this->openBlock();
        $this->post("/dashboard/absence-blocks/blocks/{$absence->id}/approve")->assertRedirect();
        $this->assertTrue($absence->fresh()->is_approved);

        $absence2 = $this->openBlock();
        $this->post("/dashboard/absence-blocks/blocks/{$absence2->id}/reject", ['admin_comment' => 'no'])->assertRedirect();
        $this->assertNotNull($absence2->fresh()->rejected_at);

        $hard = $this->openBlock(StudentAttendanceBlock::TYPE_HARD_LOCK);
        $this->post("/dashboard/absence-blocks/blocks/{$hard->id}/unlock")->assertForbidden();
        $this->assertFalse($hard->fresh()->is_approved);
    }

    public function test_super_admin_can_unlock_a_hard_lock(): void
    {
        $this->actingAs($this->user('super_admin'));

        $hard = $this->openBlock(StudentAttendanceBlock::TYPE_HARD_LOCK);
        $this->post("/dashboard/absence-blocks/blocks/{$hard->id}/unlock")->assertRedirect();

        $hard->refresh();
        $this->assertTrue($hard->is_approved);
        $this->assertSame(StudentAttendanceBlock::COMMENT_UNLOCKED, $hard->admin_comment);
    }

    public function test_admin_can_update_settings(): void
    {
        $this->actingAs($this->user('admin'));

        $this->put('/dashboard/absence-blocks/settings', [
            'absence_block_threshold' => 5,
            'post_approval_limit' => 3,
            'permission_weekly_limit' => 2,
            'cycle_anchor_date' => '2026-04-01',
        ])->assertRedirect();

        $this->assertSame('5', AttendanceRuleSetting::where('key', 'absence_block_threshold')->value('value'));
    }
}
