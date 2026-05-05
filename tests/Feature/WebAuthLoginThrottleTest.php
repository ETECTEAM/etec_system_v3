<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebAuthLoginThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        Route::middleware(['guest', 'throttle:login'])->post('/__login_throttle_test', function () {
            return response()->noContent();
        });
    }

    public function test_login_is_rate_limited_after_too_many_failed_attempts(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/__login_throttle_test', [
                'login' => 'tester',
                'password' => 'wrong-password',
            ])->assertNoContent();
        }

        $this->post('/__login_throttle_test', [
            'login' => 'tester',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}