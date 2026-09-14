<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->foreignId('attendance_session_id')->nullable()->after('student_enrollment_id')->constrained('attendance_sessions')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable()->after('attendance_date');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('location_accuracy', 10, 2)->nullable()->after('longitude');
            $table->decimal('distance_from_class', 10, 2)->nullable()->after('location_accuracy');
            $table->string('ip_address', 45)->nullable()->after('distance_from_class');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('browser')->nullable()->after('user_agent');
            $table->string('operating_system')->nullable()->after('browser');
            $table->string('device_type')->nullable()->after('operating_system');
            $table->string('device_identifier')->nullable()->after('device_type');
            $table->string('verification_status', 20)->default('verified')->after('status');
            $table->string('verification_reason')->nullable()->after('verification_status');

            $table->unique(['study_class_id', 'student_id', 'attendance_date'], 'student_attendance_unique_student_day');
        });
    }

    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table): void {
            $table->dropUnique('student_attendance_unique_student_day');
            $table->dropConstrainedForeignId('attendance_session_id');
            $table->dropColumn([
                'latitude',
                'longitude',
                'location_accuracy',
                'distance_from_class',
                'ip_address',
                'user_agent',
                'browser',
                'operating_system',
                'device_type',
                'device_identifier',
                'verification_status',
                'verification_reason',
            ]);
        });
    }
};
