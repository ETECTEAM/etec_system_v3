<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['description', 'duration', 'language', 'certificate_available']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
            $table->unsignedInteger('duration')->default(0)->after('level');
            $table->string('language')->nullable();
            $table->boolean('certificate_available')->default(false);
        });
    }
};
