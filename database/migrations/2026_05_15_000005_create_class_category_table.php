<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_category', function (Blueprint $table) {
            $table->id('class_category_id');
            $table->foreignId('class_type_id')
                ->constrained('class_type', 'class_type_id')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('category_name', 100);
            $table->string('category_code', 50)->nullable();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['class_type_id', 'category_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_category');
    }
};