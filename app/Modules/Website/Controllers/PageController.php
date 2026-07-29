<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Modules\Website\Requests\PageRequest;
use App\Modules\Website\Services\WebsiteContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly WebsiteContentService $website,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $query = Page::query()
            ->with(['menus:id,page_id,name,is_active,position', 'hero:id,page_id,is_active,background_image'])
            ->withCount('menus');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"));
        }

        match ($request->string('status')->toString()) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            'hero' => $query->whereHas('hero', fn ($q) => $q->where('is_active', true)),
            'no_hero' => $query->where(fn ($q) => $q
                ->whereDoesntHave('hero')
                ->orWhereHas('hero', fn ($hero) => $hero->where('is_active', false))),
            default => null,
        };

        return Inertia::render('backend/website/PagesIndex', [
            'pages' => $query->latest('id')->paginate(10)->withQueryString()->through(
                fn (Page $page): array => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'is_active' => $page->is_active,
                    'created_at' => $page->created_at?->format('Y-m-d'),
                    'connected_menu' => $page->menus->pluck('name')->join(', '),
                    'menus_count' => $page->menus_count,
                    'hero_status' => $page->hero?->is_active ? 'Hero Enabled' : 'Hero Disabled',
                ],
            ),
            'filters' => [
                'search' => $search,
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        return Inertia::render('backend/website/PageForm', [
            'pageData' => null,
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = DB::transaction(function () use ($request): Page {
            $page = Page::create([
                'title' => $request->validated('title'),
                'slug' => $this->uniqueSlug($request->validated('title')),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->saveHero($request, $page);

            return $page;
        });

        return redirect("/dashboard/website/pages/{$page->id}/edit")->with('success', 'Page created successfully.');
    }

    public function show(Request $request, Page $page): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        return Inertia::render('backend/website/PageShow', [
            'pageData' => $this->website->presentPage($page),
        ]);
    }

    public function edit(Request $request, Page $page): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        return Inertia::render('backend/website/PageForm', [
            'pageData' => $this->website->presentPage($page),
        ]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        DB::transaction(function () use ($request, $page): void {
            $page->update([
                'title' => $request->validated('title'),
                'slug' => $this->uniqueSlug($request->validated('title'), $page),
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->saveHero($request, $page);
        });

        return back()->with('success', 'Page updated successfully.');
    }

    public function destroy(Request $request, Page $page): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $page->load(['menus', 'hero']);

        if ($page->menus->isNotEmpty()) {
            return back()->with('error', 'This page is connected to one or more menus: '.$page->menus->pluck('name')->join(', ').'.');
        }

        DB::transaction(function () use ($page): void {
            $heroImage = $page->hero?->background_image;
            $page->hero?->delete();
            $page->delete();
            $this->website->deletePublicFile($heroImage);
        });

        return redirect('/dashboard/website/pages')->with('success', 'Page deleted successfully.');
    }

    public function updateStatus(Request $request, Page $page): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $page->update(['is_active' => $validated['is_active']]);

        return back()->with('success', $page->is_active ? 'Page activated successfully.' : 'Page deactivated successfully.');
    }

    private function uniqueSlug(string $title, ?Page $page = null): string
    {
        $baseSlug = Str::slug($title) ?: 'page';
        $slug = $baseSlug;
        $suffix = 2;

        while (Page::query()
            ->where('slug', $slug)
            ->when($page, fn ($query) => $query->whereKeyNot($page->id))
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function removeHeroImage(Request $request, Page $page): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $hero = $page->hero;
        $oldImage = $hero?->background_image;

        $hero?->update(['background_image' => null]);
        $this->website->deletePublicFile($oldImage);

        return back()->with('success', 'Hero image removed successfully.');
    }

    public function preview(Request $request, Page $page): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        return Inertia::render('frontend/pages/Show', [
            'pageData' => $this->website->presentPage($page),
            'preview' => true,
        ]);
    }

    private function saveHero(PageRequest $request, Page $page): void
    {
        $hero = $page->hero()->firstOrNew();
        $oldImage = $hero->background_image;
        $backgroundImage = $oldImage;

        if ($request->boolean('remove_hero_image')) {
            $backgroundImage = null;
        }

        if ($request->hasFile('hero_background_image')) {
            $backgroundImage = $this->website->uniqueUploadPath($request->file('hero_background_image'), 'uploads/pages/heroes');
        }

        $hero->fill([
            'title' => $request->validated('hero_title'),
            'subtitle' => $request->validated('hero_subtitle'),
            'description' => $request->validated('hero_description'),
            'background_image' => $backgroundImage,
            'primary_button_text' => $request->validated('primary_button_text'),
            'primary_button_url' => $request->validated('primary_button_url'),
            'secondary_button_text' => $request->validated('secondary_button_text'),
            'secondary_button_url' => $request->validated('secondary_button_url'),
            'overlay_opacity' => (int) $request->validated('overlay_opacity'),
            'text_alignment' => $request->validated('text_alignment'),
            'is_active' => $request->boolean('hero_is_active'),
        ]);

        $page->hero()->save($hero);

        if (($request->hasFile('hero_background_image') || $request->boolean('remove_hero_image')) && $oldImage !== $backgroundImage) {
            $this->website->deletePublicFile($oldImage);
        }

        $this->saveHeroSliderImages($request, $hero);
    }

    private function saveHeroSliderImages(PageRequest $request, \App\Models\PageHero $hero): void
    {
        $removeIds = collect($request->validated('remove_hero_images', []))
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($removeIds) {
            $images = $hero->images()->whereIn('id', $removeIds)->get();

            foreach ($images as $image) {
                $this->website->deletePublicFile($image->image);
                $image->delete();
            }
        }

        foreach ($request->validated('hero_image_states', []) as $id => $isActive) {
            $hero->images()
                ->whereKey((int) $id)
                ->update(['is_active' => (bool) $isActive]);
        }

        $nextPosition = ((int) $hero->images()->max('position')) + 1;

        foreach ($request->file('hero_slider_images', []) as $file) {
            $hero->images()->create([
                'image' => $this->website->uniqueUploadPath($file, 'uploads/pages/heroes/slides'),
                'position' => $nextPosition++,
                'is_active' => true,
            ]);
        }
    }
}
