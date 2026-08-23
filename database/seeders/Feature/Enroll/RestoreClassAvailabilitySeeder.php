<?php

namespace Database\Seeders\Feature\Enroll;

use App\Models\InstructorAvailability;
use App\Models\InstructorData;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RestoreClassAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        Room::query()->update([
            'status' => 'available',
        ]);

        InstructorData::query()->update([
            'available_for_class' => true,
            'status' => true,
        ]);

        InstructorAvailability::query()->update([
            'is_active' => true,
        ]);

        $this->command?->info('All rooms and instructors were restored to the available state.');
    }
}
