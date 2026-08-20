<?php

namespace Database\Seeders\WorkSchedule;

use App\Models\Time;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class WorkScheduleSeeder extends Seeder
{
    private const WEEKDAYS = [1, 2, 3, 4];
    private const WEEKEND = [6, 7];

    /**
     * Seed the instructor work-schedule source of truth.
     *
     * A Time record is included only when its complete range falls within a
     * configured work window. This deliberately supports overlapping Time
     * records used by different class types without creating any new records.
     */
    public function run(): void
    {
        $times = Time::query()->orderBy('id')->get(['id', 'time_name']);
        $parsedTimes = $times->map(function (Time $time): ?array {
            $range = $this->parseTimeRange($time->time_name);

            if ($range === null) {
                Log::warning('Skipping Time record with an unparseable time_name while seeding work schedules.', [
                    'time_id' => $time->id,
                    'time_name' => $time->time_name,
                ]);

                return null;
            }

            return ['id' => $time->id, ...$range];
        })->filter()->values();

        foreach ($this->schedules() as $definition) {
            $schedule = WorkSchedule::updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_active' => true,
                ],
            );

            $entries = $this->entriesForWindows($parsedTimes, $definition['windows']);
            $desiredKeys = [];

            foreach ($entries as $entry) {
                $desiredKeys[] = "{$entry['day_of_week']}:{$entry['time_id']}";

                $schedule->times()->updateOrCreate(
                    [
                        'day_of_week' => $entry['day_of_week'],
                        'time_id' => $entry['time_id'],
                    ],
                );
            }

            // Keep managed schedule codes in sync if a prior run had obsolete entries.
            $schedule->times()->get()->each(function ($entry) use ($desiredKeys): void {
                if (! in_array("{$entry->day_of_week}:{$entry->time_id}", $desiredKeys, true)) {
                    $entry->delete();
                }
            });
        }
    }

    private function schedules(): array
    {
        return [
            $this->schedule('full_time_morning_afternoon_weekend_morning', 'Morning + Afternoon + Weekend Morning', 'full-time instructor: weekday daytime and weekend morning', [
                ...$this->windows(self::WEEKDAYS, [['09:00 am', '05:30 pm']]),
                ...$this->windows(self::WEEKEND, [['08:00 am', '01:30 pm']]),
            ]),
            $this->schedule('full_time_morning_evening_weekend_morning', 'Morning + Evening + Weekend Morning', 'full-time instructor: weekday morning/evening and weekend morning', [
                ...$this->windows(self::WEEKDAYS, [['09:00 am', '12:15 pm'], ['05:00 pm', '08:30 pm']]),
                ...$this->windows(self::WEEKEND, [['08:00 am', '01:30 pm']]),
            ]),
            $this->schedule('full_time_morning_evening_weekend_afternoon', 'Morning + Evening + Weekend Afternoon', 'full-time instructor: weekday morning/evening and weekend afternoon', [
                ...$this->windows(self::WEEKDAYS, [['09:00 am', '12:15 pm'], ['05:00 pm', '08:30 pm']]),
                ...$this->windows(self::WEEKEND, [['11:00 am', '05:00 pm']]),
            ]),
            $this->schedule('full_time_afternoon_evening_weekend_morning', 'Afternoon + Evening + Weekend Morning', 'full-time instructor: weekday afternoon and weekend morning', [
                ...$this->windows(self::WEEKDAYS, [['12:30 pm', '05:00 pm']]),
                ...$this->windows(self::WEEKEND, [['08:00 am', '01:30 pm']]),
            ]),
            $this->schedule('full_time_afternoon_evening_weekend_afternoon', 'Afternoon + Evening + Weekend Afternoon', 'full-time instructor: weekday afternoon and weekend afternoon', [
                ...$this->windows(self::WEEKDAYS, [['12:30 pm', '05:00 pm']]),
                ...$this->windows(self::WEEKEND, [['11:00 am', '05:00 pm']]),
            ]),
            $this->schedule('full_time_morning_afternoon_weekend_afternoon', 'Morning + Afternoon + Weekend Afternoon', 'full-time instructor: weekday daytime and weekend afternoon', [
                ...$this->windows(self::WEEKDAYS, [['09:00 am', '05:30 pm']]),
                ...$this->windows(self::WEEKEND, [['11:00 am', '05:00 pm']]),
            ]),
            $this->schedule('part_time_weekend_afternoon', 'Weekend Afternoon', 'part-time instructor: weekend afternoon only', [
                ...$this->windows(self::WEEKEND, [['11:00 am', '05:00 pm']]),
            ]),
            $this->schedule('part_time_weekend_morning', 'Weekend Morning', 'part-time instructor: weekend morning only', [
                ...$this->windows(self::WEEKEND, [['08:00 am', '01:30 pm']]),
            ]),
        ];
    }

    private function schedule(string $code, string $name, string $description, array $windows): array
    {
        return compact('code', 'name', 'description', 'windows');
    }

    private function windows(array $days, array $ranges): array
    {
        $windows = [];

        foreach ($days as $day) {
            foreach ($ranges as [$start, $end]) {
                $windows[] = [
                    'day_of_week' => $day,
                    'start' => $this->timeToMinutes($start),
                    'end' => $this->timeToMinutes($end),
                ];
            }
        }

        return $windows;
    }

    private function entriesForWindows($times, array $windows): array
    {
        $entries = [];

        foreach ($windows as $window) {
            $matches = $times->filter(
                fn (array $time) => $time['start'] >= $window['start'] && $time['end'] <= $window['end']
            )->values();

            // Weekend windows should only offer the widest block for a given
            // span (e.g. 08:00-11:00) rather than every weekday-granularity
            // sub-block that also happens to fit inside it (e.g. 09:00-10:30).
            if (in_array($window['day_of_week'], self::WEEKEND, true)) {
                $matches = $this->dropNestedRanges($matches);
            }

            foreach ($matches as $time) {
                $entries[] = ['day_of_week' => $window['day_of_week'], 'time_id' => $time['id']];
            }
        }

        return $entries;
    }

    private function dropNestedRanges($times)
    {
        return $times->reject(
            fn (array $time) => $times->contains(fn (array $other) => $other['id'] !== $time['id']
                && $other['start'] <= $time['start']
                && $other['end'] >= $time['end']
                && ($other['start'] < $time['start'] || $other['end'] > $time['end']))
        )->values();
    }

    private function parseTimeRange(string $timeName): ?array
    {
        if (! preg_match('/^\s*(\d{1,2}:\d{2}\s*[ap]m)\s*-\s*(\d{1,2}:\d{2}\s*[ap]m)\s*$/i', $timeName, $matches)) {
            return null;
        }

        return [
            'start' => $this->timeToMinutes($matches[1]),
            'end' => $this->timeToMinutes($matches[2]),
        ];
    }

    private function timeToMinutes(string $time): int
    {
        [$hour, $minute, $meridiem] = sscanf(strtolower(trim($time)), '%d:%d %2s');
        $hour = $hour % 12 + ($meridiem === 'pm' ? 12 : 0);

        return $hour * 60 + $minute;
    }
}
