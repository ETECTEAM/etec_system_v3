<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('course_lessons', 'slug')) {
            Schema::table('course_lessons', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('title');
            });
        }

        if (! Schema::hasColumn('course_lessons', 'description')) {
            Schema::table('course_lessons', function (Blueprint $table) {
                $table->text('description')->nullable()->after('slug');
            });
        }

        if (! Schema::hasColumn('course_lessons', 'content')) {
            Schema::table('course_lessons', function (Blueprint $table) {
                $table->longText('content')->nullable()->after('description');
            });
        }

        if (! Schema::hasColumn('course_lessons', 'video_url')) {
            Schema::table('course_lessons', function (Blueprint $table) {
                $table->string('video_url')->nullable()->after('content');
            });
        }

        if (! Schema::hasColumn('course_lessons', 'duration')) {
            Schema::table('course_lessons', function (Blueprint $table) {
                $table->unsignedInteger('duration')->default(0)->after('video_url');
            });
        }
    }

    public function down(): void
    {
        foreach (['slug', 'description', 'content', 'video_url', 'duration'] as $column) {
            if (Schema::hasColumn('course_lessons', $column)) {
                Schema::table('course_lessons', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
