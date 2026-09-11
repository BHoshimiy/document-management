<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MenuRequest;
use App\Models\Category;
use App\Models\DocumentFolder;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MenuController extends Controller
{
    /** Client-facing standard page: the folders belonging to one standard. */
    public function show(Request $request, Menu $menu): View
    {
        // Tab pills: the global default categories plus this menu's own defaults.
        $categories = Category::query()
            ->forMenu($menu->id)
            ->ordered()
            ->get();

        // An unknown slug resolves to null, which reads as "All".
        $activeCategory = $categories->firstWhere('slug', $request->string('category')->toString());
        $categoryId = $activeCategory?->id;
        $search = $request->string('search')->toString();

        $folders = DocumentFolder::query()
            ->where('menu_id', $menu->id)
            ->when($categoryId, fn ($q, $categoryId) => $q->where('category_id', $categoryId))
            ->when($search, fn ($q, $search) => $q->whereTranslationLike('name', $search))
            ->ordered()
            ->withCount('documents')
            ->get();

        return view('menus.show', compact('menu', 'folders', 'categories', 'activeCategory', 'search'));
    }

    /* ---------------- admin CRUD ---------------- */

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Menu::class);

        $menus = Menu::query()
            ->when($request->string('search')->toString(),
                fn ($q, $term) => $q->whereTranslationLike('name', $term))
            ->ordered()
            ->withCount('documentFolders')
            ->paginate(24)
            ->withQueryString();

        return view('menus.index', compact('menus'));
    }

    public function create(): View
    {
        $this->authorize('create', Menu::class);

        return view('menus.create', ['menu' => new Menu]);
    }

    public function store(MenuRequest $request): RedirectResponse
    {
        $this->authorize('create', Menu::class);

        Menu::create($request->validated());

        return redirect()
            ->route('admin.menus.index')
            ->with('status', __('menus.created'));
    }

    public function edit(Menu $menu): View
    {
        $this->authorize('update', $menu);

        return view('menus.edit', compact('menu'));
    }

    public function update(MenuRequest $request, Menu $menu): RedirectResponse
    {
        $this->authorize('update', $menu);

        $menu->update($request->validated());

        return redirect()
            ->route('admin.menus.index')
            ->with('status', __('menus.updated'));
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $this->authorize('delete', $menu);

        if ($menu->documentFolders()->exists()) {
            return back()->withErrors(['menu' => __('menus.has_folders')]);
        }

        $menu->delete();

        return redirect()
            ->route('admin.menus.index')
            ->with('status', __('menus.deleted'));
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->authorize('reorder', Menu::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:menus,id'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['ids'] as $position => $id) {
                Menu::whereKey($id)->update(['order' => $position]);
            }
        });

        return back()->with('status', __('menus.reordered'));
    }
}
