<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;

// Fake users (admins + a batch of instructors with shifts / availabilities)
use Database\Seeders\Core\UserSeeder as FakeUserSeeder;

/**
 * Demo / throwaway data for local + development environments, so a new
 * feature can be clicked through without hand-building users and classes.
 *
 * NOT run in production (see DatabaseSeeder). Runs AFTER ProductionSeeder,
 * so all reference data (roles, work schedules, times, courses...) already
 * exists.
 *
 * Note: Core\UserSeeder truncates `users` / `instructor_data` and recreates
 * superadmin@etec.com + admin@etec.com + 10 instructors. In dev that is
 * fine; it just means the super-admin password is reset to "password".
 *
 * The one-off scenario seeders under database/seeders/Feature/* are
 * deliberately NOT called here - several are mutually exclusive (e.g.
 * FullClassSeeder marks every room/instructor unavailable) and are meant to
 * be run by hand:
 *   php artisan db:seed --class="Database\Seeders\Feature\Enroll\FullClassSeeder"
 */
class DevSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FakeUserSeeder::class,

            // Add more demo-data seeders here (students, classes, enrollments...)
        ]);
    }
}
