<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate time_name rows, keeping the lowest id for each.
        $duplicates = DB::table('times')
            ->select('time_name', DB::raw('MIN(id) as keep_id'))
            ->groupBy('time_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $keepId = $dup->keep_id;

            // Re-point foreign keys that reference duplicate rows.
            foreach (['schedule_time', 'instructor_schedule_blocks', 'work_schedule_times'] as $table) {
                if (Schema::hasTable($table) && Schema::getColumnListing($table) !== []) {
                    $columns = Schema::getColumnListing($table);
                    $fkColumn = in_array('time_id', $columns) ? 'time_id' : null;
                    if ($fkColumn) {
                        DB::table($table)
                            ->where($fkColumn, '!=', $keepId)
                            ->whereIn($fkColumn, function ($q) use ($dup) {
                                $q->select('id')
                                    ->from('times')
                                    ->where('time_name', $dup->time_name);
                            })
                            ->update([$fkColumn => $keepId]);
                    }
                }
            }

            DB::table('times')
                ->where('time_name', $dup->time_name)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('times', function (Blueprint $table) {
            $table->unique('time_name');
        });
    }

    public function down(): void
    {
        Schema::table('times', function (Blueprint $table) {
            $table->dropUnique(['time_name']);
        });
    }
};
