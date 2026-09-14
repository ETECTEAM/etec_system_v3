<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_certificate_requests', function (Blueprint $table): void {
            $table->json('requested_student_ids')->nullable()->after('student_count');
        });
    }

    public function down(): void
    {
        Schema::table('class_certificate_requests', function (Blueprint $table): void {
            $table->dropColumn('requested_student_ids');
        });
    }
};
