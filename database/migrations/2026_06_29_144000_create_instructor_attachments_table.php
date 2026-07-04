<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('instructor_data')->cascadeOnDelete();
            $table->string('type');
            $table->string('title')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path');
            $table->string('file_mime')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['instructor_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_attachments');
    }
};
