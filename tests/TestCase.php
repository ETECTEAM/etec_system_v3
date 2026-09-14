<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // The test suite must never reach the network. Both TelegramService
        // classes (OTP approval + error logs) and the registration IP-geolocation
        // lookup all send through Laravel's Http client, so faking it here IS the
        // Telegram fake - irazasyed/telegram-bot-sdk v3 has no Telegram::fake().
        // preventStrayRequests() turns any un-faked call into a failure instead of
        // a silent real request to the admin Telegram chat / ip-api.com. A test
        // that needs specific responses just calls Http::fake([...]) itself; that
        // overrides this catch-all while preventStrayRequests() stays in effect.
        Http::preventStrayRequests();
        Http::fake();
    }
}
