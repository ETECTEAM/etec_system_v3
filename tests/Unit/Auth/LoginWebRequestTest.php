<?php

namespace Tests\Unit\Auth;

use App\Modules\Auth\Requests\LoginWebRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LoginWebRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): ValidatorContract
    {
        $request = LoginWebRequest::create('/login', 'POST', $data);

        return Validator::make($request->all(), $request->rules());
    }

    public function test_password_is_required(): void
    {
        $validator = $this->validate(['login' => 'user@etec.com']);

        $this->assertTrue($validator->errors()->has('password'));
    }

    public function test_login_or_email_must_be_present(): void
    {
        $validator = $this->validate(['password' => 'password123']);

        $this->assertTrue($validator->errors()->has('login'));
        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_non_etec_email_addresses_are_rejected(): void
    {
        $validator = $this->validate(['login' => 'user@gmail.com', 'password' => 'password123']);

        $this->assertTrue($validator->errors()->has('login'));
    }

    public function test_etec_email_passes(): void
    {
        $validator = $this->validate(['login' => 'user@etec.com', 'password' => 'password123']);

        $this->assertFalse($validator->fails());
    }

    public function test_plain_username_passes_the_email_domain_check(): void
    {
        $validator = $this->validate(['login' => 'sokha', 'password' => 'password123']);

        $this->assertFalse($validator->fails());
    }
}
