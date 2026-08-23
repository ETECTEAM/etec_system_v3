<?php

namespace Database\Seeders\Feature\Enroll;

use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\Room;
use Illuminate\Database\Seeder;

/**
 * Makes every room and teacher unavailable so the public registration flow
 * is forced to the "no room / no instructor available" branch.
 *
 * Run manually (not part of the default db:seed run):
 *   php artisan db:seed --class="Database\Seeders\Feature\Enroll\FullClassSeeder"
 */
class FullClassSeeder extends Seeder
{
    public function run(): void
    {
        Room::query()->update([
            'status' => 'occupied',
        ]);

        InstructorData::query()->update([
            'available_for_class' => false,
            'status' => false,
        ]);

        InstructorAvailability::query()->update([
            'is_active' => false,
        ]);

        $this->command?->info('All rooms are now occupied and all instructors are marked unavailable for class assignment.');
        $this->command?->info('Test the public registration flow now to confirm it creates a pending registration instead of assigning a class.');
    }
}
