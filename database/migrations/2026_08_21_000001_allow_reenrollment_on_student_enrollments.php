<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The unique (study_class_id, student_id) constraint used to span all
// enrollment statuses, which blocked legitimately creating a fresh active
// enrollment once an older row for the same class existed in a non-active
// state (e.g. MoveStudentEnrollment cancels the source row, so a later
// move back into that class threw an unhandled QueryException).
//
// The constraint now only applies to ACTIVE enrollments:
//  - MySQL uses a virtual generated column that is NULL for every non-active
//    row (NULLs are allowed to repeat in a unique index), so only active
//    rows are deduplicated. VIRTUAL (not STORED) is required here: MySQL
//    rejects adding a STORED generated column to this table while its
//    foreign keys exist (error 1215).
//  - PostgreSQL/SQLite use a native partial unique index.
//
// Note: the composite unique also served as the index backing the
// study_class_id FK, so a standalone study_class_id index is ensured before
// the composite is dropped. Every step is guarded so the migration also
// recovers a DB left in a partially-migrated state.
return new class extends Migration
{
    private const OLD_UNIQUE_INDEX = 'student_enrollments_study_class_id_student_id_unique';

    private const ACTIVE_KEY_INDEX = 'student_enrollments_active_key_unique';

    private const STUDY_CLASS_ID_INDEX = 'student_enrollments_study_class_id_index';

    private const ACTIVE_KEY_COLUMN = 'active_enrollment_key';

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            if (! Schema::hasIndex('student_enrollments', self::STUDY_CLASS_ID_INDEX)) {
                DB::statement('ALTER TABLE student_enrollments ADD INDEX student_enrollments_study_class_id_index (study_class_id)');
            }

            if (Schema::hasIndex('student_enrollments', self::OLD_UNIQUE_INDEX)) {
                DB::statement('ALTER TABLE student_enrollments DROP INDEX student_enrollments_study_class_id_student_id_unique');
            }

            if (! Schema::hasColumn('student_enrollments', self::ACTIVE_KEY_COLUMN)) {
                DB::statement('ALTER TABLE student_enrollments ADD COLUMN active_enrollment_key VARCHAR(50) GENERATED ALWAYS AS (CASE WHEN enrollment_status = \'active\' THEN CONCAT(study_class_id, \':\', student_id) ELSE NULL END) VIRTUAL');
            }

            if (! Schema::hasIndex('student_enrollments', self::ACTIVE_KEY_INDEX)) {
                DB::statement('ALTER TABLE student_enrollments ADD UNIQUE INDEX student_enrollments_active_key_unique (active_enrollment_key)');
            }

            return;
        }

        if (Schema::hasIndex('student_enrollments', self::OLD_UNIQUE_INDEX)) {
            Schema::table('student_enrollments', function ($table) {
                $table->dropUnique(self::OLD_UNIQUE_INDEX);
            });
        }

        if (! Schema::hasIndex('student_enrollments', self::ACTIVE_KEY_INDEX)) {
            DB::statement('CREATE UNIQUE INDEX student_enrollments_active_key_unique ON student_enrollments (study_class_id, student_id) WHERE enrollment_status = \'active\'');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            if (Schema::hasIndex('student_enrollments', self::ACTIVE_KEY_INDEX)) {
                DB::statement('ALTER TABLE student_enrollments DROP INDEX student_enrollments_active_key_unique');
            }

            if (Schema::hasColumn('student_enrollments', self::ACTIVE_KEY_COLUMN)) {
                DB::statement('ALTER TABLE student_enrollments DROP COLUMN active_enrollment_key');
            }

            if (! Schema::hasIndex('student_enrollments', self::OLD_UNIQUE_INDEX)) {
                Schema::table('student_enrollments', function ($table) {
                    $table->unique(['study_class_id', 'student_id'], self::OLD_UNIQUE_INDEX);
                });
            }

            if (Schema::hasIndex('student_enrollments', self::STUDY_CLASS_ID_INDEX)) {
                DB::statement('ALTER TABLE student_enrollments DROP INDEX student_enrollments_study_class_id_index');
            }

            return;
        }

        if (Schema::hasIndex('student_enrollments', self::ACTIVE_KEY_INDEX)) {
            DB::statement('DROP INDEX student_enrollments_active_key_unique');
        }

        if (! Schema::hasIndex('student_enrollments', self::OLD_UNIQUE_INDEX)) {
            Schema::table('student_enrollments', function ($table) {
                $table->unique(['study_class_id', 'student_id'], self::OLD_UNIQUE_INDEX);
            });
        }
    }
};