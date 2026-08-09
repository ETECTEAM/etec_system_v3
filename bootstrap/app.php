<?php

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
        // បន្ថែមបន្ទាត់នេះ ដើម្បីឱ្យ Laravel ឈប់ខ្វល់រឿងជម្លោះ HTTPS/SSL ចាស់
        $middleware->trustProxies(at: '*');

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
    ->withExceptions(function (Exceptions $exceptions): void {
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