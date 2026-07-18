<?php

namespace Tests\Unit\User;

use App\Models\User;
use App\Modules\User\Requests\UpdateUserRequest;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateUserRequestTest extends TestCase
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

    private function validate(User $actor, User $target, array $data): ValidatorContract
    {
        $request = UpdateUserRequest::create("/dashboard/users/{$target->id}", 'PUT', $data);
        $request->setUserResolver(fn () => $actor);

        $route = new Route('PUT', '/dashboard/users/{user}', []);
        $route->bind($request);
        $route->setParameter('user', $target);
        $request->setRouteResolver(fn () => $route);

        return Validator::make($request->all(), $request->rules());
    }

    private function validPayload(User $target): array
    {
        return [
            'name' => 'Updated Name',
            'email' => $target->email,
            'role' => 'instructor',
            'account_status' => 'active',
        ];
    }

    public function test_password_is_optional_on_update(): void
    {
        $target = User::factory()->create();

        $validator = $this->validate($this->superAdmin(), $target, $this->validPayload($target));

        $this->assertFalse($validator->fails());
    }

    public function test_password_must_be_confirmed_when_provided(): void
    {
        $target = User::factory()->create();
        $data = array_merge($this->validPayload($target), [
            'password' => 'newpassword123',
            'password_confirmation' => 'different',
        ]);

        $validator = $this->validate($this->superAdmin(), $target, $data);

        $this->assertTrue($validator->errors()->has('password'));
    }

    public function test_admin_cannot_assign_a_role_outside_their_authority(): void
    {
        $target = User::factory()->create();
        $data = array_merge($this->validPayload($target), ['role' => 'super_admin']);

        $validator = $this->validate($this->admin(), $target, $data);

        $this->assertTrue($validator->errors()->has('role'));
    }

    public function test_email_unique_rule_ignores_the_user_being_updated(): void
    {
        $target = User::factory()->create(['email' => 'keep.me@etec.com']);

        $validator = $this->validate($this->superAdmin(), $target, $this->validPayload($target));

        $this->assertFalse($validator->errors()->has('email'));
    }

    public function test_email_unique_rule_still_rejects_another_users_email(): void
    {
        $other = User::factory()->create(['email' => 'taken@etec.com']);
        $target = User::factory()->create();

        $data = array_merge($this->validPayload($target), ['email' => 'taken@etec.com']);

        $validator = $this->validate($this->superAdmin(), $target, $data);

        $this->assertTrue($validator->errors()->has('email'));
    }
}
