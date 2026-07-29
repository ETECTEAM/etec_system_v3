<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WebsiteVideo;
use App\Modules\Website\Requests\WebsiteVideoRequest;
use App\Modules\Website\Services\WebsiteContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteVideoController extends Controller
{
    public function __construct(
        private readonly WebsiteContentService $website,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $query = WebsiteVideo::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        match ($request->string('status')->toString()) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            'featured' => $query->where('is_featured', true),
            default => null,
        };

        return Inertia::render('backend/website/VideosIndex', [
            'videos' => $query
                ->orderBy('sort_order')
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (WebsiteVideo $video): array => $this->presentVideo($video)),
            'filters' => [
                'search' => $search,
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        return Inertia::render('backend/website/VideoForm', [
            'videoData' => null,
        ]);
    }

    public function store(WebsiteVideoRequest $request): RedirectResponse
    {
        $video = WebsiteVideo::create($this->validatedPayload($request));

        return redirect("/dashboard/website/videos/{$video->id}/edit")->with('success', 'Video created successfully.');
    }

    public function edit(Request $request, WebsiteVideo $video): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        return Inertia::render('backend/website/VideoForm', [
            'videoData' => $this->presentVideo($video),
        ]);
    }

    public function update(WebsiteVideoRequest $request, WebsiteVideo $video): RedirectResponse
    {
        $oldVideo = $video->video_path;
        $oldThumbnail = $video->thumbnail_path;

        $video->update($this->validatedPayload($request, $video));

        if ($request->hasFile('video') && $oldVideo !== $video->video_path) {
            $this->website->deletePublicFile($oldVideo);
        }

        if (($request->hasFile('thumbnail') || $request->boolean('remove_thumbnail')) && $oldThumbnail !== $video->thumbnail_path) {
            $this->website->deletePublicFile($oldThumbnail);
        }

        return back()->with('success', 'Video updated successfully.');
    }

    public function destroy(Request $request, WebsiteVideo $video): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $videoPath = $video->video_path;
        $thumbnailPath = $video->thumbnail_path;

        $video->delete();
        $this->website->deletePublicFile($videoPath);
        $this->website->deletePublicFile($thumbnailPath);

        return redirect('/dashboard/website/videos')->with('success', 'Video deleted successfully.');
    }

    public function updateStatus(Request $request, WebsiteVideo $video): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $video->update(['is_active' => $validated['is_active']]);

        return back()->with('success', $video->is_active ? 'Video activated successfully.' : 'Video deactivated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(WebsiteVideoRequest $request, ?WebsiteVideo $video = null): array
    {
        $videoPath = $video?->video_path;
        $thumbnailPath = $video?->thumbnail_path;

        if ($request->hasFile('video')) {
            $videoPath = $this->website->uniqueUploadPath($request->file('video'), 'uploads/videos');
        }

        if ($request->boolean('remove_thumbnail')) {
            $thumbnailPath = null;
        }

        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $this->website->uniqueUploadPath($request->file('thumbnail'), 'uploads/videos/thumbnails');
        }

        return [
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'video_path' => $videoPath,
            'thumbnail_path' => $thumbnailPath,
            'duration' => $request->validated('duration'),
            'sort_order' => (int) $request->validated('sort_order'),
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentVideo(WebsiteVideo $video): array
    {
        return [
            'id' => $video->id,
            'title' => $video->title,
            'description' => $video->description,
            'video_path' => $video->video_path,
            'video_url' => $video->video_url,
            'thumbnail_path' => $video->thumbnail_path,
            'thumbnail_url' => $video->thumbnail_url,
            'duration' => $video->duration,
            'views_count' => $video->views_count,
            'sort_order' => $video->sort_order,
            'is_featured' => $video->is_featured,
            'is_active' => $video->is_active,
            'created_at' => $video->created_at?->format('Y-m-d'),
        ];
    }
}
