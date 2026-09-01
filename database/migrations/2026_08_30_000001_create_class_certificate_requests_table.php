<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_certificate_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('study_class_id')->unique()->constrained('study_classes')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('certificate_type', 30)->default('normal');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('student_count')->default(0);
            $table->text('note')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['certificate_type', 'status']);
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_certificate_requests');
    }
};
