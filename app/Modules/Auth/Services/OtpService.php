<?php

namespace App\Modules\Auth\Services;

use App\Models\OtpVerification;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Creates and verifies one-time registration codes.
 */
class OtpService
{
    public function __construct(
        private readonly AuthAuditService $auditService,
        private readonly TelegramService $telegramService,
    ) {}

    public function createForUser(User $user, ?int $createdBy = null): array
    {
        $plainCode = (string) random_int(100000, 999999);

        // Store only a hash so the plain OTP is never saved in the database.
        $otp = OtpVerification::create([
            'user_id' => $user->id,
            'otp_code' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'created_by' => $createdBy,
        ]);

        $this->auditService->log($user, 'otp.created', request()->ip(), [
            'otp_id' => $otp->id,
        ], $createdBy);

        $this->telegramService->sendOtpLog($this->formatOtpLogMessage(
            title: 'OTP GENERATED',
            user: $user,
            action: 'Registration',
            status: 'Generated',
            details: [
                'OTP ID: '.$otp->id,
            ],
        ));

        return [$otp, $plainCode];
    }

    public function verify(User $user, string $code): OtpVerification
    {
        // Always verify against the latest unverified OTP for this user.
        $otp = OtpVerification::query()
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->isExpired()) {
            $this->telegramService->sendOtpLog($this->formatOtpLogMessage(
                title: 'OTP VERIFICATION FAILED',
                user: $user,
                action: 'Registration',
                status: 'Expired',
                details: [
                    'Reason: No active verification code found or the code expired.',
                ],
            ));

            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        if ($otp->attempts >= 5) {
            $this->telegramService->sendOtpLog($this->formatOtpLogMessage(
                title: 'OTP BLOCKED',
                user: $user,
                action: 'Registration',
                status: 'Blocked',
                details: [
                    'Reason: Too many attempts.',
                    'Attempts: '.$otp->attempts,
                ],
            ));

            throw ValidationException::withMessages([
                'code' => ['Too many attempts. Please request a new code.'],
            ]);
        }

        if (! Hash::check($code, $otp->otp_code)) {
            $otp->increment('attempts');
            $attempts = $otp->attempts + 1;

            // Failed attempts are audited so abuse can be investigated later.
            $this->auditService->log($user, 'otp.failed', request()->ip(), [
                'otp_id' => $otp->id,
                'attempts' => $attempts,
            ]);

            $this->telegramService->sendOtpLog($this->formatOtpLogMessage(
                title: 'OTP VERIFICATION FAILED',
                user: $user,
                action: 'Registration',
                status: 'Invalid OTP',
                details: [
                    'Reason: Incorrect verification code.',
                    'Attempts: '.$attempts,
                    'OTP ID: '.$otp->id,
                ],
            ));

            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        $otp->forceFill([
            'verified_at' => now(),
        ])->save();

        $this->auditService->log($user, 'otp.verified', request()->ip(), [
            'otp_id' => $otp->id,
        ]);

        $this->telegramService->sendOtpLog($this->formatOtpLogMessage(
            title: 'OTP VERIFIED',
            user: $user,
            action: 'Registration',
            status: 'Success',
            details: [
                'Username: '.$this->displayUsername($user),
                'OTP ID: '.$otp->id,
            ],
        ));

        return $otp;
    }

    private function formatOtpLogMessage(string $title, User $user, string $action, string $status, array $details = []): string
    {
        $lines = array_merge([
            $title,
            '',
            'Name: '.$user->name,
            'Username: '.$this->displayUsername($user),
            'Action: '.$action,
            'Time: '.now()->format('Y-m-d H:i:s'),
            'Status: '.$status,
        ], $details);

        return implode("\n", array_filter($lines, static fn ($value) => $value !== null && $value !== ''));
    }

    private function displayUsername(User $user): string
    {
        return $user->email ?: 'n/a';
    }
}
