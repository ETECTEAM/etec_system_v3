<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'user_id')) {
                $table->foreignId('user_id')
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            }

            if (Schema::hasColumn('students', 'full_name')) {
                $table->dropColumn('full_name');
            }

            if (Schema::hasColumn('students', 'status')) {
                $table->renameColumn('status', 'student_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            if (! Schema::hasColumn('students', 'full_name')) {
                $table->string('full_name')->nullable()->after('id');
            }

            if (Schema::hasColumn('students', 'student_status')) {
                $table->renameColumn('student_status', 'status');
            }
        });
    }
};