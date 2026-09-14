<?php

use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'otp' => [
        'enabled' => env('OTP_VERIFICATION_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timing-Safe Login Dummy Hash
    |--------------------------------------------------------------------------
    |
    | Checked by AuthController::loginWeb() when no user is found for a login
    | attempt, so the response time for a nonexistent account matches a real
    | Hash::check() and doesn't leak account existence. Must be a valid bcrypt
    | hash whose cost matches BCRYPT_ROUNDS. Generate a new one per environment
    | with: password_hash('anything', PASSWORD_BCRYPT, ['cost' => 12]).
    |
    */

    'dummy_password_hash' => env(
        'AUTH_DUMMY_PASSWORD_HASH',
        '$2y$12$tHtyWiLhB93IiFOwLuWrnuF2pjUt8EY14lb9rJnnATJGw/gUTBbMC'
    ),

    /*
    |--------------------------------------------------------------------------
    | Role-Based Token Expiration
    |--------------------------------------------------------------------------
    |
    | How long an authenticated session/token stays valid after it is issued,
    | keyed by the user's backend role. Durations are ISO-8601 intervals so
    | the expiry is an exact offset from the login/issue time - 'P1M' lands on
    | the same clock time one calendar month later, 'P1Y' one year later -
    | rather than a calendar boundary. Letting an instructor log in on
    | August 29, 2026 at 10:00 yields an expiry of September 29, 2026 at 10:00;
    | an admin on the same date expires August 29, 2027 at 10:00.
    |
    | Roles without an entry here (e.g. student) keep the existing behavior:
    | their access-window columns stay null and they never expire.
    |
    */

    'token_expiration' => [
        'roles' => [
            'instructor' => env('INSTRUCTOR_TOKEN_EXPIRATION', 'P1M'),
            'admin' => env('ADMIN_TOKEN_EXPIRATION', 'P1Y'),
            'super_admin' => env('SUPER_ADMIN_TOKEN_EXPIRATION', 'P1Y'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 30,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
