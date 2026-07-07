# Running Tests

## Module tests

Feature and Unit tests mirror the `app/Modules/*` layout:

```text
tests/Feature/User/
  UserControllerTest.php
  UserManagementControllerTest.php

tests/Unit/User/
  UserServiceTest.php
  UserApprovalServiceTest.php
  UserPolicyTest.php
  StoreUserRequestTest.php
  UpdateUserRequestTest.php
  StoreUserDataTest.php
  UpdateUserDataTest.php

tests/Feature/Auth/
  AuthControllerTest.php        (login, logout, /instructor-register, code-verify)

tests/Unit/Auth/
  AuthServiceTest.php
  OtpServiceTest.php
  AuthAuditServiceTest.php
  TelegramServiceTest.php
  LoginWebRequestTest.php
  RegisterWebRequestTest.php
  VerifyCodeRequestTest.php
```

These run against the real database configured in `.env` (`DB_CONNECTION=mysql`) — there is no separate SQLite/test database. Each test is wrapped in a transaction via `RefreshDatabase` and rolled back afterwards, so it won't leave data behind.

### Known environment quirks in the `system_app` container

- **`APP_KEY`**: the container's real environment has an **empty** `APP_KEY`, which shadows the working key in `.env` (env vars take priority over `.env` values). Without overriding it, any test that touches a web route (session/cookies) fails with `MissingAppKeyException`. Until that's fixed at the container level, pass the key explicitly on `docker exec` as shown below.
- **`CACHE_STORE=file`**: the container also exports `CACHE_STORE=file`, overriding phpunit.xml's `array` setting. That means cache written by tests (locks, rate-limiter counters) can survive between runs. Tests that depend on cache state should force the array store themselves (see `TelegramServiceTest::setUp()`).

### If you use Docker (`system_app` container)

Run every module test (Feature + Unit) in one command:

```bash
docker exec -e APP_KEY="base64:xet3D32raau+J6B2BVoUK6MTIW/I6/RdNcPT8tvavAQ=" system_app \
  bash -lc "cd /var/www && php artisan test tests/Feature tests/Unit"
```

Run just one module (e.g. Auth):

```bash
docker exec -e APP_KEY="base64:xet3D32raau+J6B2BVoUK6MTIW/I6/RdNcPT8tvavAQ=" system_app \
  bash -lc "cd /var/www && php artisan test tests/Feature/Auth tests/Unit/Auth"
```

Run a single file:

```bash
docker exec -e APP_KEY="base64:xet3D32raau+J6B2BVoUK6MTIW/I6/RdNcPT8tvavAQ=" system_app \
  bash -lc "cd /var/www && php artisan test tests/Feature/User/UserControllerTest.php"
```

Run a single test case by name:

```bash
docker exec -e APP_KEY="base64:xet3D32raau+J6B2BVoUK6MTIW/I6/RdNcPT8tvavAQ=" system_app \
  bash -lc "cd /var/www && php artisan test --filter=test_super_admin_can_create_an_instructor_user"
```

### If you run PHP locally (no Docker)

As long as your local `.env` has a valid `APP_KEY` and points at a reachable MySQL database, just run:

```bash
php artisan test
```

Or a single module:

```bash
php artisan test tests/Feature/Auth tests/Unit/Auth
php artisan test tests/Feature/User tests/Unit/User
```

Or directly via PHPUnit:

```bash
./vendor/bin/phpunit tests/Feature tests/Unit
```

If you get `MissingAppKeyException` locally, generate a fresh key (only if `.env` doesn't already have one — don't overwrite a working key others rely on):

```bash
php artisan key:generate
```
