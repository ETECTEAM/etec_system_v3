<?php

use App\Models\StudyClass;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_classes', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique()->after('title');
        });

        StudyClass::query()
            ->select(['id', 'title', 'slug'])
            ->orderBy('id')
            ->get()
            ->each(function (StudyClass $studyClass): void {
                if ($studyClass->slug) {
                    return;
                }

                $studyClass->forceFill([
                    'slug' => StudyClass::uniqueSlug($studyClass->title, $studyClass->id),
                ])->save();
            });
    }

    public function down(): void
    {
        Schema::table('study_classes', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
