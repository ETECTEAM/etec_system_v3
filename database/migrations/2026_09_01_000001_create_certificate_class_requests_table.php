<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_class_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('study_class_id')->constrained('study_classes')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('certificate_type', 30);
            $table->string('status', 20)->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();

            $table->unique(['study_class_id', 'certificate_type'], 'certificate_class_requests_unique');
            $table->index(['certificate_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_class_requests');
    }
};
