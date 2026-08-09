<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\User\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        RateLimiter::for('login', function (Request $request): Limit {
            $login = trim((string) ($request->input('login') ?? $request->input('email') ?? ''));

            return Limit::perMinute(5)->by(strtolower($login).'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request): Limit {
            $email = trim((string) $request->input('email', ''));

            return Limit::perMinute(5)->by(strtolower($email).'|'.$request->ip());
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

        Gate::before(function ($user, string $ability): ?bool {
            return $user->hasRole('super_admin') ? true : null;
        });
    }
}
