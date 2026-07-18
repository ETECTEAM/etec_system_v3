<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use App\Modules\Auth\Requests\VerifyCodeRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class VerifyCodeRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): ValidatorContract
    {
        $request = VerifyCodeRequest::create('/api/code-verify', 'POST', $data);

        return Validator::make($request->all(), $request->rules());
    }

    public function test_code_is_required(): void
    {
        $this->assertTrue($this->validate([])->errors()->has('code'));
    }

    public function test_code_must_be_exactly_six_digits(): void
    {
        $this->assertTrue($this->validate(['code' => '12345'])->errors()->has('code'));
        $this->assertTrue($this->validate(['code' => '1234567'])->errors()->has('code'));
        $this->assertTrue($this->validate(['code' => 'abc123'])->errors()->has('code'));
        $this->assertFalse($this->validate(['code' => '123456'])->fails());
    }

    public function test_user_id_must_reference_an_existing_user(): void
    {
        $this->assertTrue($this->validate(['code' => '123456', 'user_id' => 999999])->errors()->has('user_id'));

        $user = User::factory()->create();
        $this->assertFalse($this->validate(['code' => '123456', 'user_id' => $user->id])->fails());
    }

    public function test_user_id_is_optional(): void
    {
        $this->assertFalse($this->validate(['code' => '123456'])->fails());
    }
}
