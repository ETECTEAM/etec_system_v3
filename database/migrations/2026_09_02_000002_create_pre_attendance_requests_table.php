<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_class_id')->constrained('study_classes')->cascadeOnDelete();
            $table->foreignId('class_session_id')->nullable()->constrained('class_sessions')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('session_date');
            $table->string('session_status', 20);
            $table->string('status', 20)->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['study_class_id', 'session_date', 'requested_by'], 'pre_att_request_unique_session_teacher');
            $table->index(['status', 'session_date']);
            $table->index(['requested_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_attendance_requests');
    }
};
