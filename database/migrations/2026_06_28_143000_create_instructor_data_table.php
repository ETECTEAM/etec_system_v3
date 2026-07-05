<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name')->nullable();
            $table->string('instructor_code')->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->string('employment_type')->nullable();
            $table->string('shift_group')->nullable();
            $table->boolean('available_for_class')->default(true);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index('user_id');
            $table->index('instructor_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_data');
    }
};
