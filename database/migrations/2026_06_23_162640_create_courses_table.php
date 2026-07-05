<?php
// database/migrations/2026_06_23_000004_create_courses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_track_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('level')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('thumbnail')->nullable();
            $table->string('language')->nullable();
            $table->boolean('certificate_available')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
