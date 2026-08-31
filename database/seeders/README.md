# Database Seeders

Seeders are split into two groups:

| Folder | Class | Contains | Runs in production? |
| --- | --- | --- | --- |
| `Production/` | `Production\ProductionSeeder` | Reference / lookup data (permissions, roles, settings, categories, class types, terms, times, work schedules, buildings, courses, schedule grid, website menu) + **one real super-admin login**. Every seeder here is idempotent. | **Yes** |
| `Dev/` | `Dev\DevSeeder` | Fake users (admins + 10 instructors with shifts) and any other demo data for trying out a new feature locally. | **No** |

The per-domain folders (`Permission/`, `Course/`, `Class/`, `Feature/`, ...) still
hold the individual seeders — `ProductionSeeder` / `DevSeeder` just call them in
the right order.

## Default run

```bash
php artisan db:seed
```

`DatabaseSeeder` always runs `ProductionSeeder`, then runs `DevSeeder` **unless
`APP_ENV=production`**. So locally you get reference data + demo data; a real
deploy (`php artisan db:seed --force`) gets reference data only.

## Run one group explicitly

```bash
# reference data only (also safe to re-run on a live DB)
php artisan db:seed --class="Database\Seeders\Production\ProductionSeeder"
composer seed-prod

# fake users / demo data only (needs ProductionSeeder to have run first)
php artisan db:seed --class="Database\Seeders\Dev\DevSeeder"
composer seed-dev
```

## Production super-admin credentials

`Production\SuperAdminSeeder` creates a single login and never truncates
`users`. Override the defaults with env vars before deploying:

```dotenv
SEEDER_SUPERADMIN_EMAIL=superadmin@etec.com
SEEDER_SUPERADMIN_NAME="Super Admin"
SEEDER_SUPERADMIN_PASSWORD=change-me
```

Default password is `password` — change it for any real environment.

## One-off scenario seeders (`Feature/*`)

These are **not** part of `db:seed`. They set up a specific scenario and some are
mutually exclusive (e.g. `FullClassSeeder` marks every room/instructor
unavailable). Run them by hand when you need them:

```bash
php artisan db:seed --class="Database\Seeders\Feature\Report\ReportSeeder"
php artisan db:seed --class="Database\Seeders\Feature\Report\LandRentReportSeeder"
php artisan db:seed --class="Database\Seeders\Feature\Invoice\InvoiceTestSeeder"
php artisan db:seed --class="Database\Seeders\Feature\Enroll\FullClassSeeder"
php artisan db:seed --class="Database\Seeders\Feature\Enroll\RestoreClassAvailabilitySeeder"
php artisan db:seed --class="Database\Seeders\Feature\Enroll\JustStartedClassSeeder"
php artisan db:seed --class="Database\Seeders\Feature\Attendance\PreAttendanceTestSeeder"
```

## Other useful targets

```bash
# rebuild permissions + roles + fake users from scratch (truncates)
php artisan db:seed --class="Database\Seeders\Core\CoreSeeder"

# building / floor / room only
php artisan db:seed --class="Database\Seeders\Building\BuildingSeeder"
```
