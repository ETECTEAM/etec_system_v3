<?php

namespace Tests\Unit\User;

use App\Models\User;
use App\Modules\User\Requests\StoreUserRequest;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreUserRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function validate(User $actor, array $data): ValidatorContract
    {
        $request = StoreUserRequest::create('/dashboard/users', 'POST', $data);
        $request->setUserResolver(fn () => $actor);

        return Validator::make($request->all(), $request->rules());
    }

    private function validInstructorPayload(): array
    {
        return [
            'name' => 'New Instructor',
            'email' => 'new.instructor@etec.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'instructor',
            'account_status' => 'active',
        ];
    }

    public function test_fails_when_required_fields_are_missing(): void
    {
        $validator = $this->validate($this->superAdmin(), []);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('email'));
        $this->assertTrue($validator->errors()->has('password'));
        $this->assertTrue($validator->errors()->has('role'));
        $this->assertTrue($validator->errors()->has('account_status'));
    }

    public function test_fails_when_email_domain_is_not_etec(): void
    {
        $data = array_merge($this->validInstructorPayload(), ['email' => 'someone@gmail.com']);

        $validator = $this->validate($this->superAdmin(), $data);

        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_fails_when_password_confirmation_does_not_match(): void
    {
        $data = array_merge($this->validInstructorPayload(), ['password_confirmation' => 'something-else']);

        $validator = $this->validate($this->superAdmin(), $data);

        $this->assertTrue($validator->errors()->has('password'));
    }

    public function test_fails_when_email_is_already_taken(): void
    {
        $existing = User::factory()->create();
        $data = array_merge($this->validInstructorPayload(), ['email' => $existing->email]);

        $validator = $this->validate($this->superAdmin(), $data);

        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_admin_cannot_assign_a_role_outside_their_authority(): void
    {
        $data = array_merge($this->validInstructorPayload(), ['role' => 'super_admin']);

        $validator = $this->validate($this->admin(), $data);

        $this->assertTrue($validator->errors()->has('role'));
    }

    public function test_admin_can_assign_an_operational_role(): void
    {
        $validator = $this->validate($this->admin(), $this->validInstructorPayload());

        $this->assertFalse($validator->fails());
    }

    public function test_passes_with_a_complete_valid_payload(): void
    {
        $validator = $this->validate($this->superAdmin(), $this->validInstructorPayload());

        $this->assertFalse($validator->fails());
    }
}
