<?php

namespace App\Modules\Auth\Controllers\Telegram;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Modules\Auth\Services\AuthAuditService;
use App\Modules\User\Services\UserApprovalService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Telegram\Bot\Laravel\Facades\Telegram;

/**
 * Handles Telegram inline-button callbacks for user approval and rejection.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, UserApprovalService $approvalService, AuthAuditService $auditService): Response
    {
        $secret = config('telegram.webhook_secret');

        // Fail closed: an unconfigured secret must reject every request, not
        // accept them unauthenticated. Telegram must send the matching
        // X-Telegram-Bot-Api-Secret-Token header (set via setWebhook's
        // secret_token param) on every call.
        if (! $secret || $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return response()->noContent(401);
        }

        $update = Telegram::getWebhookUpdate();
        $callback = $update->callbackQuery;

        if (! $callback || ! isset($callback['data'])) {
            return response()->noContent();
        }

        $data = (string) $callback['data'];
        [$action, $otpId] = array_pad(explode(':', $data, 2), 2, null);

        // Supported callback payloads are approve:{otp_id} and reject:{otp_id}.
        if (! in_array($action, ['approve', 'reject'], true) || ! $otpId) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callback['id'],
                'text' => 'Invalid action.',
                'show_alert' => false,
            ]);

            return response()->noContent();
        }

        $otp = OtpVerification::query()->with('user')->find($otpId);

        if (! $otp || ! $otp->user) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callback['id'],
                'text' => 'OTP not found.',
                'show_alert' => false,
            ]);

            return response()->noContent();
        }

        $user = $otp->user;

        if ($user->status === UserStatus::Rejected) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callback['id'],
                'text' => 'User already rejected.',
                'show_alert' => false,
            ]);

            return response()->noContent();
        }

        if ($action === 'approve') {
            $approvalService->approve($user, null, 'telegram');

            Telegram::answerCallbackQuery([
                'callback_query_id' => $callback['id'],
                'text' => 'User approved.',
                'show_alert' => false,
            ]);

            $auditService->log($user, 'telegram.approved', $request->ip(), [
                'otp_id' => $otp->id,
            ]);

            return response()->noContent();
        }

        $approvalService->reject($user, null, 'telegram');

        Telegram::answerCallbackQuery([
            'callback_query_id' => $callback['id'],
            'text' => 'User rejected.',
            'show_alert' => false,
        ]);

        $auditService->log($user, 'telegram.rejected', $request->ip(), [
            'otp_id' => $otp->id,
        ]);

        return response()->noContent();
    }
}
