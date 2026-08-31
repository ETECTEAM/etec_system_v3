<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;

/**
 * Demo / throwaway data for local + development environments, so a new
 * feature can be clicked through without hand-building users and classes.
 *
 * NOT run in production (see DatabaseSeeder). Runs AFTER ProductionSeeder,
 * so all reference data (roles, work schedules, times, courses...) already
 * exists. The super-admin login comes from ProductionSeeder\SuperAdminSeeder.
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
            // Plain admin login (role: admin).
            DevAdminSeeder::class,

            // instructor1@etec.com .. instructorN@etec.com - one per active
            // work schedule, so every shift shape is covered.
            InstructorWorkScheduleSeeder::class,

            // Add more demo-data seeders here (students, classes, enrollments...)
        ]);
    }
}
