<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gender on the account itself, so every role has one - admins included, who have
     * no profile table to keep it in. Nullable: only students are required to have
     * it (students.gender is NOT NULL), and existing accounts may not know theirs.
     *
     * students.gender / instructor_data.gender stay as they are and are written from
     * this value when a student or instructor is saved.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female'])->nullable()->after('email');
        });

        // Carry over what the profile tables already hold so existing accounts show a gender.
        DB::table('users')
            ->join('students', 'students.user_id', '=', 'users.id')
            ->update(['users.gender' => DB::raw('students.gender')]);

        // instructor_data.gender is free text, so only copy the two values the enum accepts.
        DB::table('users')
            ->join('instructor_data', 'instructor_data.user_id', '=', 'users.id')
            ->whereRaw("LOWER(TRIM(instructor_data.gender)) in ('male', 'female')")
            ->update(['users.gender' => DB::raw('LOWER(TRIM(instructor_data.gender))')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
