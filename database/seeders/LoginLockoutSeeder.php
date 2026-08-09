<?php

namespace Database\Seeders;

use App\Models\LoginLockoutSetting;
use App\Models\LoginLockoutTier;
use Illuminate\Database\Seeder;

class LoginLockoutSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            1 => 1,
            2 => 5,
            3 => 15,
        ];

        foreach ($tiers as $offenseNumber => $durationMinutes) {
            LoginLockoutTier::updateOrCreate(
                ['offense_number' => $offenseNumber],
                ['duration_minutes' => $durationMinutes],
            );
        }

        LoginLockoutSetting::query()->firstOrCreate(['id' => 1], ['reset_after_hours' => 24]);
    }
}
