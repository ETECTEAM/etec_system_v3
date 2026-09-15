<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

// Presence (required when configured) is enforced by the caller's rule set,
// not here - see RegisterWebRequest::rules().
class ValidTurnstile implements ValidationRule
{
    public function __construct(private readonly ?string $ip = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret_key');

        // Not configured (local/dev, or before the site is behind a real
        // domain) - don't block registration on a captcha that isn't set up.
        // The test suite forces this empty (see phpunit.xml).
        if (! $secret) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Please complete the verification challenge.');

            return;
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $secret,
            'response' => $value,
            'remoteip' => $this->ip,
        ]);

        if (! $response->ok() || $response->json('success') !== true) {
            $fail('Verification challenge failed. Please try again.');
        }
    }
}
