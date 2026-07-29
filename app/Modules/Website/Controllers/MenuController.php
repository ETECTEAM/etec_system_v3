<?php

namespace App\Modules\Website\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Page;
use App\Modules\Website\Requests\MenuRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $query = Menu::query()
            ->with(['page:id,title,slug,is_active', 'parent:id,name'])
            ->withCount('children');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('page', fn ($page) => $page->where('title', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")));
        }

        return Inertia::render('backend/website/MenusIndex', [
            'menus' => $query->orderByRaw('COALESCE(parent_id, id)')
                ->orderByRaw('parent_id IS NOT NULL')
                ->orderBy('position')
                ->orderBy('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Menu $menu): array => [
                'id' => $menu->id,
                'name' => $menu->name,
                'parent_id' => $menu->parent_id,
                'children_count' => $menu->children_count,
                'position' => $menu->position,
                'is_active' => $menu->is_active,
                'resolved_url' => $menu->resolved_url,
                'parent' => $menu->parent ? [
                    'id' => $menu->parent->id,
                    'name' => $menu->parent->name,
                ] : null,
                'page' => $menu->page ? [
                    'id' => $menu->page->id,
                    'title' => $menu->page->title,
                    'slug' => $menu->page->slug,
                    'is_active' => $menu->page->is_active,
                ] : null,
            ]),
            'pages' => $this->pageOptions(),
            'parentMenus' => $this->parentMenuOptions(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        return Inertia::render('backend/website/MenuForm', [
            'menu' => null,
            'pages' => $this->pageOptions(),
            'parentMenus' => $this->parentMenuOptions(),
        ]);
    }

    public function store(MenuRequest $request): RedirectResponse
    {
        Menu::create([
            ...$request->safe()->except('position'),
            'position' => (Menu::where('parent_id', $request->input('parent_id'))->max('position') ?? 0) + 1,
        ]);

        return redirect('/dashboard/website/menus')->with('success', 'Menu created successfully.');
    }

    public function edit(Request $request, Menu $menu): Response
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $menu->load(['page:id,title,slug,is_active', 'parent:id,name']);

        return Inertia::render('backend/website/MenuForm', [
            'menu' => $menu,
            'pages' => $this->pageOptions(),
            'parentMenus' => $this->parentMenuOptions($menu),
        ]);
    }

    public function update(MenuRequest $request, Menu $menu): RedirectResponse
    {
        $menu->update($request->safe()->except('position'));

        return redirect('/dashboard/website/menus')->with('success', 'Menu updated successfully.');
    }

    public function destroy(Request $request, Menu $menu): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $menu->delete();

        return back()->with('success', 'Menu deleted successfully.');
    }

    public function updateStatus(Request $request, Menu $menu): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $menu->update(['is_active' => $validated['is_active']]);

        return back()->with('success', $menu->is_active ? 'Menu activated successfully.' : 'Menu deactivated successfully.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['super_admin', 'admin']), 403);

        $validated = $request->validate([
            'menus' => ['required', 'array', 'min:1'],
            'menus.*.id' => ['required', 'integer', 'exists:menus,id'],
            'menus.*.position' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['menus'] as $item) {
                Menu::whereKey($item['id'])->update(['position' => $item['position']]);
            }
        });

        return back()->with('success', 'Menu order updated successfully.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pageOptions(): array
    {
        return Page::query()
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'is_active'])
            ->map(fn (Page $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'is_active' => $page->is_active,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parentMenuOptions(?Menu $editing = null): array
    {
        return Menu::query()
            ->whereNull('parent_id')
            ->when($editing, fn ($query) => $query->whereKeyNot($editing->id))
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Menu $menu): array => [
                'id' => $menu->id,
                'name' => $menu->name,
            ])
            ->all();
    }
}
