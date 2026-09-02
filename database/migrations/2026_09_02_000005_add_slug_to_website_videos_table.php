<?php

use App\Models\WebsiteVideo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_videos', function (Blueprint $table): void {
            $table->string('slug')->nullable()->unique()->after('title');
        });

        WebsiteVideo::query()
            ->select(['id', 'title', 'slug'])
            ->orderBy('id')
            ->get()
            ->each(function (WebsiteVideo $video): void {
                if ($video->slug) {
                    return;
                }

                $video->forceFill([
                    'slug' => WebsiteVideo::uniqueSlug($video->title, $video->id),
                ])->save();
            });
    }

    public function down(): void
    {
        Schema::table('website_videos', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
