<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // The course/schedule a student registered interest in, before a formal
            // class (with room/instructor) is assigned to them. Recorded so the
            // registration receipt (course, term, time, fee) survives after printing.
            $table->foreignId('course_id')->nullable()->after('user_id')->constrained('courses')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->after('course_id')->constrained('terms')->nullOnDelete();
            $table->foreignId('time_id')->nullable()->after('term_id')->constrained('times')->nullOnDelete();
            $table->decimal('fee_amount', 12, 2)->nullable()->after('time_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_id');
            $table->dropConstrainedForeignId('term_id');
            $table->dropConstrainedForeignId('time_id');
            $table->dropColumn('fee_amount');
        });
    }
};
