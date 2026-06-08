<?php

namespace App\Modules\Auth\Responses;

/**
 * Builds JSON responses for OTP verification endpoints.
 */
class VerificationResponse
{
    public static function alreadyActive(string $redirectPath)
    {
        return response()->json([
            'message' => 'Account is already active.',
            'redirect' => $redirectPath,
        ]);
    }

    public static function verified(string $redirectPath)
    {
        return response()->json([
            'message' => 'Account verified successfully.',
            'redirect' => $redirectPath,
        ]);
    }
}
