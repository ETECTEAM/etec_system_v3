# Running Tests

## User module tests

Feature and Unit tests for `app/Modules/User` live in:

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
```

These run against the real database configured in `.env` (`DB_CONNECTION=mysql`) — there is no separate SQLite/test database. Each test is wrapped in a transaction via `RefreshDatabase` and rolled back afterwards, so it won't leave data behind.

### Known environment quirk: `APP_KEY`

In the `system_app` Docker container, the shell's real environment has an **empty** `APP_KEY`, which shadows the working key in `.env` (env vars take priority over `.env` values). Without overriding it, any test that touches a web route (session/cookies) fails with `MissingAppKeyException`. Until that's fixed at the container level, pass the key explicitly on `docker exec` as shown below.

### If you use Docker (`system_app` container)

Run every User test (Feature + Unit) in one command:

```bash
docker exec -e APP_KEY="base64:xet3D32raau+J6B2BVoUK6MTIW/I6/RdNcPT8tvavAQ=" system_app \
  bash -lc "cd /var/www && php artisan test tests/Feature/User tests/Unit/User"
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
php artisan test tests/Feature/User tests/Unit/User
```

Or the full suite:

```bash
php artisan test
```

Or directly via PHPUnit:

```bash
./vendor/bin/phpunit tests/Feature/User tests/Unit/User
```

If you get `MissingAppKeyException` locally, generate a fresh key (only if `.env` doesn't already have one — don't overwrite a working key others rely on):

```bash
php artisan key:generate
```
