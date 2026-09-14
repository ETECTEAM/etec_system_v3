<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // decimal(10,7) mirrors study_classes.attendance_latitude / student_attendances.latitude
            // so the haversine maths behaves the same across the app.
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(150);
            $table->boolean('is_active')->default(true);
            $table->string('description', 1000)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_locations');
    }
};
