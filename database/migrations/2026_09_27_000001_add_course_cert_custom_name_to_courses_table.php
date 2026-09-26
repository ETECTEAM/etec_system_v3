<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Certificate-only wording for a course. Never shown outside certificates,
        // so the real `title` can stay untouched. NULL means "use the title".
        Schema::table('courses', function (Blueprint $table): void {
            $table->string('course_cert_custom_name', 100)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn('course_cert_custom_name');
        });
    }
};
