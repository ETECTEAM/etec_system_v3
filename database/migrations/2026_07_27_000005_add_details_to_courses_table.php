<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('courses', 'description')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->text('description')->nullable()->after('slug');
            });
        }

        if (! Schema::hasColumn('courses', 'duration')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->unsignedInteger('duration')->default(0)->after('level');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('courses', 'description')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasColumn('courses', 'duration')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('duration');
            });
        }
    }
};
