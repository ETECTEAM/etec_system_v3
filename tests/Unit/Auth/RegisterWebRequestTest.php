<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use App\Modules\Auth\Requests\RegisterWebRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RegisterWebRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): ValidatorContract
    {
        $request = RegisterWebRequest::create('/instructor-register', 'POST', $data);

        return Validator::make($request->all(), $request->rules());
    }

    private function validPayload(): array
    {
        return [
            'name' => 'New Instructor',
            'email' => 'new.instructor@etec.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }

    public function test_fails_when_required_fields_are_missing(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('email'));
        $this->assertTrue($validator->errors()->has('password'));
    }

    public function test_rejects_non_etec_email_domains(): void
    {
        $validator = $this->validate(array_merge($this->validPayload(), ['email' => 'user@gmail.com']));

        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_rejects_an_email_that_is_already_registered(): void
    {
        $existing = User::factory()->create();

        $validator = $this->validate(array_merge($this->validPayload(), ['email' => $existing->email]));

        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_rejects_a_password_shorter_than_eight_characters(): void
    {
        $validator = $this->validate(array_merge($this->validPayload(), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]));

        $this->assertTrue($validator->errors()->has('password'));
    }

    public function test_rejects_a_mismatched_password_confirmation(): void
    {
        $validator = $this->validate(array_merge($this->validPayload(), ['password_confirmation' => 'different123']));

        $this->assertTrue($validator->errors()->has('password'));
    }

    public function test_passes_with_a_valid_payload(): void
    {
        $this->assertFalse($this->validate($this->validPayload())->fails());
    }
}
