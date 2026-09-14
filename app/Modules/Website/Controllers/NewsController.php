<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Modules\Website\Requests\NewsRequest;
use App\Modules\Website\Services\WebsiteContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function __construct(
        private readonly WebsiteContentService $website,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $query = News::query()->with(['images', 'user'])->withCount('images');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($userQuery) => $userQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")));
        }

        match ($request->string('status')->toString()) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            'featured' => $query->where('is_featured', true),
            default => null,
        };

        return Inertia::render('backend/website/NewsIndex', [
            'news' => $query
                ->orderBy('sort_order')
                ->latest('published_at')
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (News $news): array => $this->presentNews($news)),
            'filters' => [
                'search' => $search,
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        return Inertia::render('backend/website/NewsForm', [
            'newsData' => null,
        ]);
    }

    public function store(NewsRequest $request): RedirectResponse
    {
        $news = DB::transaction(function () use ($request): News {
            $news = News::create($this->validatedPayload($request));
            $this->saveImages($request, $news);

            return $news;
        });

        return redirect("/dashboard/website/news/{$news->id}/edit")->with('success', 'News created successfully.');
    }

    public function edit(Request $request, News $news): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        return Inertia::render('backend/website/NewsForm', [
            'newsData' => $this->presentNews($news),
        ]);
    }

    public function update(NewsRequest $request, News $news): RedirectResponse
    {
        DB::transaction(function () use ($request, $news): void {
            $news->update($this->validatedPayload($request));
            $this->saveImages($request, $news);
        });

        return back()->with('success', 'News updated successfully.');
    }

    public function destroy(Request $request, News $news): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $news->load('images');
        $images = $news->images->pluck('image')->all();

        DB::transaction(function () use ($news): void {
            $news->delete();
        });

        // FILE: disabled - not using file uploads
        // foreach ($images as $image) {
        //     $this->website->deletePublicFile($image);
        // }

        return redirect('/dashboard/website/news')->with('success', 'News deleted successfully.');
    }

    public function updateStatus(Request $request, News $news): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $news->update(['is_active' => $validated['is_active']]);

        return back()->with('success', $news->is_active ? 'News published successfully.' : 'News unpublished successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayload(NewsRequest $request): array
    {
        $news = $request->route('news');

        return [
            'title' => $request->validated('title'),
            'slug' => $this->uniqueSlug($request->validated('title'), $news instanceof News ? $news : null),
            'user_id' => $request->user()?->id,
            'excerpt' => $request->validated('excerpt'),
            'description' => $request->validated('description'),
            'published_at' => $request->validated('published_at'),
            'sort_order' => (int) $request->validated('sort_order'),
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function saveImages(NewsRequest $request, News $news): void
    {
        $removeIds = collect($request->validated('remove_images', []))
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($removeIds) {
            $images = $news->images()->whereIn('id', $removeIds)->get();

            foreach ($images as $image) {
                // FILE: disabled - not using file uploads
                // $this->website->deletePublicFile($image->image);
                $image->delete();
            }
        }

        foreach ($request->validated('image_states', []) as $id => $isActive) {
            $news->images()
                ->whereKey((int) $id)
                ->update(['is_active' => (bool) $isActive]);
        }

        // FILE: disabled - not using file uploads
        // $nextPosition = ((int) $news->images()->max('position')) + 1;
        //
        // foreach ($request->file('images', []) as $file) {
        //     $news->images()->create([
        //         'image' => $this->website->uniqueUploadPath($file, 'uploads/news'),
        //         'position' => $nextPosition++,
        //         'is_active' => true,
        //     ]);
        // }
    }

    private function uniqueSlug(string $title, ?News $news = null): string
    {
        $baseSlug = $this->slugFromTitle($title);
        $slug = $baseSlug;
        $suffix = 2;

        while (News::query()
            ->where('slug', $slug)
            ->when($news, fn ($query) => $query->whereKeyNot($news->id))
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function slugFromTitle(string $title): string
    {
        $slug = mb_strtolower(trim($title));
        $slug = preg_replace('/[^\pL\pM\pN]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'news';
    }

    /**
     * @return array<string, mixed>
     */
    private function presentNews(News $news): array
    {
        $news->loadMissing(['images', 'user']);

        return [
            'id' => $news->id,
            'title' => $news->title,
            'slug' => $news->slug,
            'author' => $news->user?->name ?? $news->user?->email,
            'excerpt' => $news->excerpt,
            'description' => $this->website->sanitizeContent($news->description),
            'published_at' => $news->published_at?->format('Y-m-d'),
            'sort_order' => $news->sort_order,
            'is_featured' => $news->is_featured,
            'is_active' => $news->is_active,
            'images_count' => $news->images_count ?? $news->images->count(),
            'created_at' => $news->created_at?->format('Y-m-d'),
            'images' => $news->images->map(fn ($image): array => [
                'id' => $image->id,
                'image' => null,
                'image_url' => $this->website->publicImageDataUri($image->image),
                'position' => $image->position,
                'is_active' => $image->is_active,
            ])->values()->all(),
        ];
    }
}
