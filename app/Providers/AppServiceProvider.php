<?php

namespace App\Providers;

use App\Models\AttendanceRule;
use App\Models\OfficialLeave;
use App\Models\StudentAttendanceBlock;
use App\Models\User;
use App\Modules\AbsenceBlock\Policies\AttendanceRulePolicy;
use App\Modules\AbsenceBlock\Policies\StudentAttendanceBlockPolicy;
use App\Modules\OfficialLeave\Policies\OfficialLeavePolicy;
use App\Modules\User\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // URL::forceScheme('https');
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(OfficialLeave::class, OfficialLeavePolicy::class);
        Gate::policy(AttendanceRule::class, AttendanceRulePolicy::class);
        Gate::policy(StudentAttendanceBlock::class, StudentAttendanceBlockPolicy::class);

        RateLimiter::for('login', function (Request $request): Limit {
            $login = trim((string) ($request->input('login') ?? $request->input('email') ?? ''));

            return Limit::perMinute(5)->by(strtolower($login).'|'.$request->ip());
        });

        // Two independently-keyed buckets, same reasoning as password-email below:
        // without the IP-only bucket, rotating the email field on every request
        // gets a fresh 5/minute allowance each time from the same IP.
        RateLimiter::for('register', function (Request $request): array {
            $email = strtolower(trim((string) $request->input('email', '')));

            return [
                Limit::perMinute(5)->by('email:'.$email.'|'.$request->ip()),
                Limit::perMinute(10)->by('ip:'.$request->ip()),
            ];
        });

        // Two independently-keyed buckets: an account can't be spammed past
        // 3/hour regardless of source IP, and a single IP can't hammer many
        // different accounts past 20/hour.
        RateLimiter::for('password-email', function (Request $request): array {
            $email = strtolower(trim((string) $request->input('email', '')));

            return [
                Limit::perHour(3)->by('email:'.$email),
                Limit::perHour(20)->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request): Limit {
            $userId = (string) $request->input('user_id', '');

            return Limit::perMinute(5)->by($userId.'|'.$request->ip());
        });

        RateLimiter::for('telegram-webhook', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('attendance-qr-submit', function (Request $request): array {
            $studentId = (string) $request->input('student_id', 'guest');
            $token = (string) $request->route('token', '');

            return [
                Limit::perMinute(10)->by('student:'.$studentId.'|token:'.$token),
                Limit::perMinute(90)->by('ip:'.$request->ip().'|token:'.$token),
            ];
        });

        Gate::before(function ($user, string $ability): ?bool {
            return $user->hasRole('super_admin') ? true : null;
        });

        $this->assertAttendanceTimezoneConsistency();
    }

    // AutoRecordAttendanceCommand, GenerateClassSessionsCommand, and
    // SendAttendanceDigestCommand all hardcode 'Asia/Phnom_Penh' rather than
    // reading config('app.timezone'), so class_sessions' naive DATETIME
    // columns are only safe to compare against "now" as long as the two stay
    // in sync. If APP_TIMEZONE ever drifts from that hardcoded value without
    // updating the commands too, every class would instantly read as
    // past-end and get mass-marked missed - this makes the drift loud
    // instead of silent.
    private function assertAttendanceTimezoneConsistency(): void
    {
        $expected = 'Asia/Phnom_Penh';
        $configured = config('app.timezone');

        if ($configured !== $expected) {
            Log::critical(
                "APP_TIMEZONE is '{$configured}' but the attendance commands hardcode '{$expected}' - ".
                'class_sessions timestamps and attendance auto-recording will misalign until these match.'
            );
        }
    }
}
