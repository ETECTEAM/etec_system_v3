<?php

use App\Models\StudentEnrollment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->string('public_token', 64)->nullable()->unique()->after('id');
        });

        StudentEnrollment::query()
            ->select(['id', 'public_token'])
            ->orderBy('id')
            ->get()
            ->each(function (StudentEnrollment $enrollment): void {
                if ($enrollment->public_token) {
                    return;
                }

                $enrollment->forceFill([
                    'public_token' => StudentEnrollment::uniquePublicToken(),
                ])->save();
            });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
