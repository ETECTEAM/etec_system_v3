<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebRegisterNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_register_creates_verification_code_and_notification(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $response = $this->post('/register', [
            'name' => 'Instructor One',
            'email' => 'instructor1@etec.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/code-verify');

        $user = User::query()->where('email', 'instructor1@etec.com')->first();

        $this->assertNotNull($user);
        $this->assertFalse((bool) $user->is_active);
        $this->assertSame('pending', $user->status->value);

        $otp = OtpVerification::query()->where('user_id', $user->id)->latest('id')->first();

        $this->assertNotNull($otp);
        $this->assertNotNull($otp->otp_code);
        $this->assertNull($otp->verified_at);
    }

    public function test_web_register_approves_user_when_otp_verification_is_disabled(): void
    {
        config(['auth.otp.enabled' => false]);

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $response = $this->post('/register', [
            'name' => 'Instructor Two',
            'email' => 'instructor2@etec.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');

        $user = User::query()->where('email', 'instructor2@etec.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue((bool) $user->is_active);
        $this->assertSame('active', $user->status->value);
        $this->assertNotNull($user->verified_at);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseMissing('otp_verifications', [
            'user_id' => $user->id,
        ]);
    }
}
