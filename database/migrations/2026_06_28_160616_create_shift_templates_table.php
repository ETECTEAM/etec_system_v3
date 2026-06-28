<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('employment_type')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shift_template_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_template_id')->constrained('shift_templates')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->string('period')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        Schema::table('instructor_data', function (Blueprint $table) {
            $table->foreignId('shift_template_id')->nullable()->constrained('shift_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('instructor_data', function (Blueprint $table) {
            $table->dropForeign(['shift_template_id']);
            $table->dropColumn('shift_template_id');
        });

        Schema::dropIfExists('shift_template_blocks');
        Schema::dropIfExists('shift_templates');
    }
};
