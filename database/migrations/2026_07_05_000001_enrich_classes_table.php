<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->renameColumn('class_name', 'title');

            $table->foreignId('course_id')
                ->nullable()
                ->after('title')
                ->constrained('courses')
                ->nullOnDelete();

            $table->foreignId('lesson_id')
                ->nullable()
                ->after('course_id')
                ->constrained('course_lessons')
                ->nullOnDelete();

            $table->foreignId('building_id')
                ->nullable()
                ->after('lesson_id')
                ->constrained('buildings')
                ->nullOnDelete();

            $table->foreignId('floor_id')
                ->nullable()
                ->after('building_id')
                ->constrained('floors')
                ->nullOnDelete();

            $table->foreignId('room_id')
                ->nullable()
                ->after('floor_id')
                ->constrained('rooms')
                ->nullOnDelete();

            $table->foreignId('term_id')
                ->nullable()
                ->after('room_id')
                ->constrained('terms')
                ->nullOnDelete();

            $table->string('status', 50)
                ->default('active')
                ->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->renameColumn('title', 'class_name');

            $table->dropConstrainedForeignId('course_id');
            $table->dropConstrainedForeignId('lesson_id');
            $table->dropConstrainedForeignId('building_id');
            $table->dropConstrainedForeignId('floor_id');
            $table->dropConstrainedForeignId('room_id');
            $table->dropConstrainedForeignId('term_id');
            $table->dropColumn('status');
        });
    }
};
