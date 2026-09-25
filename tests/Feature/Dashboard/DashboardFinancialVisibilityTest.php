<?php

namespace Tests\Feature\Dashboard;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardFinancialVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            \Database\Seeders\Core\PermissionSeeder::class,
            \Database\Seeders\Core\RoleSeeder::class,
            \Database\Seeders\Core\AssignPermissionSeeder::class,
        ]);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_never_receives_revenue_or_payment_data(): void
    {
        $this->actingAs($this->user('admin'))
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('backend/Home')
                ->where('report.canViewFinancials', false)
                ->has('report.summary.total_students_enrolled')
                ->has('report.summary.new_enrollments')
                ->has('report.enrollmentTrend')
                ->has('report.courseStats')
                ->missing('report.revenueTrend')
                ->missing('report.paymentStatus')
                ->missing('report.summary.total_revenue_collected')
                ->missing('report.summary.average_revenue_per_enrollment')
                ->missing('report.summary.outstanding_amount')
                ->missing('report.summary.paid_enrollments')
                ->missing('report.summary.revenue_change_percent')
                ->missing('report.summary.average_revenue_change_percent'));
    }

    public function test_super_admin_receives_revenue_and_payment_data(): void
    {
        $this->actingAs($this->user('super_admin'))
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('backend/Home')
                ->where('report.canViewFinancials', true)
                ->has('report.summary.total_revenue_collected')
                ->has('report.summary.average_revenue_per_enrollment')
                ->has('report.revenueTrend')
                ->has('report.paymentStatus'));
    }
}
