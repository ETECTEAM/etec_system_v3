<?php

namespace App\Console\Commands;

use App\Models\StudentAttendanceBlock;
use App\Modules\AbsenceBlock\Actions\AutoBlockStudent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillAbsenceBlocksCommand extends Command
{
    protected $signature = 'absence-blocks:backfill {--apply : Keep the blocks; without it everything is rolled back}';

    protected $description = 'Raises absence blocks for students whose recorded absences already reach the limit (rows written outside the app never triggered one).';

    public function handle(AutoBlockStudent $autoBlock): int
    {
        // Latest absent day per student + class: a live save counts that day's month, so this matches it.
        $latest = DB::table('student_attendances')
            ->where('absent', true)
            ->select('student_id', 'study_class_id', DB::raw('max(attendance_date) as last_absent'))
            ->groupBy('student_id', 'study_class_id')
            ->get();

        $lastBlockId = (int) StudentAttendanceBlock::query()->max('id');

        DB::beginTransaction();

        try {
            foreach ($latest as $row) {
                $autoBlock->handle((int) $row->student_id, (int) $row->study_class_id, (string) $row->last_absent);
            }

            $created = StudentAttendanceBlock::query()
                ->with('student:id,full_name')
                ->where('id', '>', $lastBlockId)
                ->get();

            $this->table(
                ['Block', 'Student', 'Class', 'Type'],
                $created->map(fn ($block) => [$block->id, $block->student?->full_name, $block->study_class_id, $block->block_type])->all(),
            );

            if ($this->option('apply')) {
                DB::commit();
                $this->info("Created {$created->count()} block(s).");
            } else {
                DB::rollBack();
                $this->warn("Dry run: {$created->count()} block(s) would be created. Re-run with --apply to keep them.");
            }
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return self::SUCCESS;
    }
}
