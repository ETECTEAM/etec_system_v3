<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // specialization moves from a single free-text string to a JSON array of
    // sub-category names (see InstructorData::casts) - wrap any existing
    // plain-text value into a single-element array so old data survives the
    // new array cast instead of silently decoding to null.
    public function up(): void
    {
        DB::table('instructor_data')
            ->whereNotNull('specialization')
            ->where('specialization', '!=', '')
            ->get(['id', 'specialization'])
            ->each(function (object $row): void {
                DB::table('instructor_data')
                    ->where('id', $row->id)
                    ->update(['specialization' => json_encode([$row->specialization])]);
            });
    }

    public function down(): void
    {
        DB::table('instructor_data')
            ->whereNotNull('specialization')
            ->where('specialization', '!=', '')
            ->get(['id', 'specialization'])
            ->each(function (object $row): void {
                $decoded = json_decode((string) $row->specialization, true);
                $value = is_array($decoded) ? ($decoded[0] ?? null) : $row->specialization;

                DB::table('instructor_data')
                    ->where('id', $row->id)
                    ->update(['specialization' => $value]);
            });
    }
};
