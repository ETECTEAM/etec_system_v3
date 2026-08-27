<?php

use App\Services\TelegramService;
use App\Console\Commands\AutoRecordAttendanceCommand;
use App\Console\Commands\GenerateClassSessionsCommand;
use App\Console\Commands\SendAttendanceDigestCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust only the private-network reverse proxy (nginx, same Docker
        // Compose network) so Laravel resolves the real client IP from
        // X-Forwarded-* instead of blindly trusting any peer - '*' let anyone
        // spoof X-Forwarded-For and defeat IP-keyed rate limiting.
        $middleware->trustProxies(at: [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->statefulApi();

        $middleware->alias([
            'auth' => Authenticate::class,
            'active' => EnsureAccountIsActive::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'auth.session' => AuthenticateSession::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'guest' => RedirectIfAuthenticated::class,
            'password.confirm' => RequirePassword::class,
            'precognitive' => HandlePrecognitiveRequests::class,
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
            'throttle.redis' => ThrottleRequestsWithRedis::class,
            'verified' => EnsureEmailIsVerified::class,

            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Interval is fixed at one minute regardless of the configured grace period —
        // the grace value only changes which sessions the command finds due, not how
        // often it looks.
        $schedule->command(AutoRecordAttendanceCommand::class)
            ->everyMinute()
            ->timezone('Asia/Phnom_Penh')
            ->withoutOverlapping();

        // Creates the day's ClassSession rows just after midnight, before any class
        // could plausibly start.
        $schedule->command(GenerateClassSessionsCommand::class)
            ->dailyAt('00:05')
            ->timezone('Asia/Phnom_Penh')
            ->withoutOverlapping();

        // Late enough that the day's classes (last slot ends 20:30/8:30pm) have
        // finished, so the digest reflects the whole day.
        $schedule->command(SendAttendanceDigestCommand::class)
            ->dailyAt('22:00')
            ->timezone('Asia/Phnom_Penh')
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $e): void {
            $request = app()->bound('request') ? app('request') : null;
            $command = app()->runningInConsole() ? ($_SERVER['argv'][1] ?? 'console') : null;

            app(TelegramService::class)->sendErrorLog(
                app(TelegramService::class)->buildErrorMessage($e, $request, $command)
            );
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'));
        });

        $exceptions->render(function (TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session expired. Please login again.'], 419);
            }

            return redirect()->route('login')->with('error', 'Session expired. Please login again.');
        });

        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            $retryAfter = (int) ($e->getHeaders()['Retry-After'] ?? 60);
            $message = "Too many attempts. Please try again in {$retryAfter} seconds.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'retry_after' => $retryAfter,
                ], 429, ['Retry-After' => (string) $retryAfter]);
            }

            // Stay on the same form instead of navigating to a dedicated error page - 'login'
            // surfaces inline under the login field the same way a wrong-password error does,
            // 'throttle' is available for any other form that wants to show it, 'error' triggers
            // a toast on pages that watch page.props.flash, and retryAfter drives a countdown
            // that disables the submit button until the block actually lifts.
            return back()
                ->withErrors(['login' => $message, 'throttle' => $message])
                ->with(['error' => $message, 'retryAfter' => $retryAfter])
                ->header('Retry-After', (string) $retryAfter);
        });
    })->create();
