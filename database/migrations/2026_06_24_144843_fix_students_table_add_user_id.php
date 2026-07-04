<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'user_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('students', 'full_name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('full_name');
            });
        }

        if (Schema::hasColumn('students', 'status')) {
            DB::statement(
                "ALTER TABLE `students` CHANGE `status` `student_status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active'"
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'user_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        if (! Schema::hasColumn('students', 'full_name')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('full_name')->nullable()->after('id');
            });
        }

        if (Schema::hasColumn('students', 'student_status')) {
            DB::statement(
                "ALTER TABLE `students` CHANGE `student_status` `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active'"
            );
        }
    }
};
