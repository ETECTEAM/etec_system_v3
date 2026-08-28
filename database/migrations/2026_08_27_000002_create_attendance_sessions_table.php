<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('study_class_id')->constrained('study_classes')->cascadeOnDelete();
            $table->string('qr_token', 128)->unique();
            $table->date('attendance_date');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('stopped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('stopped_at')->nullable();
            $table->timestamps();

            $table->unique(['study_class_id', 'attendance_date']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
