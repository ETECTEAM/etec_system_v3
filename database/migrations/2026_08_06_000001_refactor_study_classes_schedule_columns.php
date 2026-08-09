<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_classes', function (Blueprint $table) {
            $table->unsignedBigInteger('class_type_id')->nullable()->after('room_id');
            $table->unsignedBigInteger('term_id')->nullable()->after('class_type_id');
            $table->unsignedBigInteger('time_id')->nullable()->after('term_id');

            $table->foreign('class_type_id')->references('class_type_id')->on('class_type')->nullOnDelete();
            $table->foreign('term_id')->references('id')->on('terms')->nullOnDelete();
            $table->foreign('time_id')->references('id')->on('times')->nullOnDelete();
        });

        // Backfill the new FK columns from the old denormalized columns so existing rows survive.
        foreach (DB::table('study_classes')->get() as $row) {
            DB::table('study_classes')->where('id', $row->id)->update([
                'class_type_id' => $this->matchClassTypeId($row->class_type),
                'term_id' => $this->matchTermId(json_decode($row->study_days ?? '[]', true) ?: []),
                'time_id' => $this->matchTimeId($row->start_time, $row->end_time),
            ]);
        }

        Schema::table('study_classes', function (Blueprint $table) {
            $table->dropIndex('study_classes_class_type_index');
            $table->dropColumn(['class_type', 'study_days', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::table('study_classes', function (Blueprint $table) {
            $table->string('class_type', 20)->default('physical')->after('room_id');
            $table->json('study_days')->nullable()->after('class_type');
            $table->time('start_time')->nullable()->after('study_days');
            $table->time('end_time')->nullable()->after('start_time');
        });

        foreach (DB::table('study_classes')->get() as $row) {
            $term = $row->term_id ? DB::table('terms')->where('id', $row->term_id)->first() : null;
            $time = $row->time_id ? DB::table('times')->where('id', $row->time_id)->first() : null;
            $classType = $row->class_type_id ? DB::table('class_type')->where('class_type_id', $row->class_type_id)->first() : null;
            $range = $this->parseTimeRange($time->time_name ?? '');

            DB::table('study_classes')->where('id', $row->id)->update([
                'class_type' => $classType && str_contains(strtolower($classType->type_name), 'online') ? 'online' : 'physical',
                'study_days' => json_encode($this->parseTermDays($term->term_name ?? '')),
                'start_time' => $range['start'] ?? null,
                'end_time' => $range['end'] ?? null,
            ]);
        }

        Schema::table('study_classes', function (Blueprint $table) {
            $table->dropForeign(['class_type_id']);
            $table->dropForeign(['term_id']);
            $table->dropForeign(['time_id']);
            $table->dropColumn(['class_type_id', 'term_id', 'time_id']);
        });
    }

    private function matchClassTypeId(?string $classType): ?int
    {
        if (! $classType) {
            return null;
        }

        $row = DB::table('class_type')
            ->where('type_name', $classType === 'online' ? 'Online Class' : 'Physical Class')
            ->first();

        return $row?->class_type_id;
    }

    private function matchTermId(array $studyDays): ?int
    {
        if (! $studyDays) {
            return null;
        }

        $key = $this->daysKey($studyDays);

        foreach (DB::table('terms')->get() as $term) {
            if ($this->daysKey($this->parseTermDays($term->term_name)) === $key) {
                return $term->id;
            }
        }

        return null;
    }

    private function matchTimeId(?string $start, ?string $end): ?int
    {
        if (! $start || ! $end) {
            return null;
        }

        $start = substr($start, 0, 5);
        $end = substr($end, 0, 5);

        foreach (DB::table('times')->get() as $time) {
            $range = $this->parseTimeRange($time->time_name);

            if (($range['start'] ?? null) === $start && ($range['end'] ?? null) === $end) {
                return $time->id;
            }
        }

        return null;
    }

    private function parseTermDays(?string $termName): array
    {
        $dayMap = [
            'Mon' => 'Monday', 'Monday' => 'Monday',
            'Tue' => 'Tuesday', 'Tues' => 'Tuesday', 'Tuesday' => 'Tuesday',
            'Wed' => 'Wednesday', 'Wednesday' => 'Wednesday',
            'Thu' => 'Thursday', 'Thur' => 'Thursday', 'Thurs' => 'Thursday', 'Thursday' => 'Thursday',
            'Fri' => 'Friday', 'Friday' => 'Friday',
            'Sat' => 'Saturday', 'Saturday' => 'Saturday',
            'Sun' => 'Sunday', 'Sunday' => 'Sunday',
        ];

        return collect(preg_split('/\s*(?:-|,|&|\/|\+|and)\s*/i', (string) $termName))
            ->map(fn (string $day) => $dayMap[trim($day)] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function daysKey(array $studyDays): string
    {
        return collect($studyDays)
            ->map(fn (string $day) => strtolower(trim($day)))
            ->sort()
            ->implode('|');
    }

    private function parseTimeRange(?string $timeName): array
    {
        $range = preg_match('/\(([^)]+)\)/', (string) $timeName, $match) ? $match[1] : $timeName;
        [$start, $end] = array_pad(preg_split('/\s*-\s*/', trim((string) $range)), 2, null);

        return [
            'start' => $this->toHm($start),
            'end' => $this->toHm($end),
        ];
    }

    private function toHm(?string $time): ?string
    {
        $time = trim((string) $time);

        if ($time === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $time, $match)) {
            $hour = (int) $match[1];
            $minute = (int) $match[2];
            $meridiem = strtoupper($match[3]);

            if ($meridiem === 'AM' && $hour === 12) {
                $hour = 0;
            }
            if ($meridiem === 'PM' && $hour !== 12) {
                $hour += 12;
            }

            return sprintf('%02d:%02d', $hour, $minute);
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $match)) {
            return sprintf('%02d:%02d', (int) $match[1], (int) $match[2]);
        }

        return null;
    }
};
