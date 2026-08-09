<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('page_hero_images')) {
            return;
        }

        Schema::create('page_hero_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_hero_id')->constrained('page_heroes')->cascadeOnDelete();
            $table->string('image');
            $table->unsignedInteger('position')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_hero_images');
    }
};
