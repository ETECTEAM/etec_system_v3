<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_classes', function (Blueprint $table): void {
            $table->decimal('attendance_latitude', 10, 7)->nullable()->after('document_price');
            $table->decimal('attendance_longitude', 10, 7)->nullable()->after('attendance_latitude');
            $table->unsignedInteger('attendance_radius_meters')->nullable()->after('attendance_longitude');
            $table->string('allowed_ip_ranges')->nullable()->after('attendance_radius_meters');
            $table->string('attendance_ip_policy', 20)->default('suspicious')->after('allowed_ip_ranges');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->string('attendance_pin_hash')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('attendance_pin_hash');
        });

        Schema::table('study_classes', function (Blueprint $table): void {
            $table->dropColumn([
                'attendance_latitude',
                'attendance_longitude',
                'attendance_radius_meters',
                'allowed_ip_ranges',
                'attendance_ip_policy',
            ]);
        });
    }
};
