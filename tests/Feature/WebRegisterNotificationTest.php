<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Models\VerificationCode;
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

        $response->assertRedirect('/verify-code');

        $user = User::query()->where('email', 'instructor1@etec.com')->first();

        $this->assertNotNull($user);
        $this->assertFalse((bool) $user->is_active);

        $verificationCode = VerificationCode::query()->where('user_id', $user->id)->latest('id')->first();

        $this->assertNotNull($verificationCode);
        $this->assertSame(6, strlen((string) $verificationCode->code));
        $this->assertFalse((bool) $verificationCode->is_verified);

        $notification = Notification::query()->latest('id')->first();

        $this->assertNotNull($notification);
        $this->assertSame('New Instructor Registered', $notification->title);
        $this->assertStringContainsString('Instructor One registered. Code:', $notification->message);
        $this->assertFalse((bool) $notification->is_read);
    }
}
