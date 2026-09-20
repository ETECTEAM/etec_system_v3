<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The date of a class's first real instructor-saved attendance. Auto-record and the instructor
     * block only apply to sessions dated after it, so a brand-new class (or one whose history was
     * imported from the old system) is left alone until its instructor has tracked once.
     *
     * Classes already running auto-record are armed from their earliest processed session, so
     * deploying this doesn't switch auto-record off for them.
     */
    public function up(): void
    {
        Schema::table('study_classes', function (Blueprint $table): void {
            $table->date('auto_record_started_on')->nullable()->after('end_date');
        });

        $firstProcessed = DB::table('class_sessions')
            ->whereIn('status', ['recorded', 'partial', 'pre_attendance', 'auto_recorded'])
            ->groupBy('study_class_id')
            ->selectRaw('study_class_id, min(session_date) as first_date')
            ->get();

        foreach ($firstProcessed as $row) {
            DB::table('study_classes')
                ->where('id', $row->study_class_id)
                ->update(['auto_record_started_on' => $row->first_date]);
        }
    }

    public function down(): void
    {
        Schema::table('study_classes', function (Blueprint $table): void {
            $table->dropColumn('auto_record_started_on');
        });
    }
};
