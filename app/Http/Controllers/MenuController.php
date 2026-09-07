<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MenuRequest;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MenuController extends Controller
{
    /** Dashboard: the template library — every folder across every standard. */
    public function index(): View
    {
        $menus = Menu::query()
            ->ordered()
            ->with(['documentFolders' => fn ($q) => $q->ordered()->withCount('documents')])
            ->get();

        $categories = \App\Models\Category::query()->ordered()->get();

        return view('dashboard.index', compact('menus', 'categories'));
    }

    public function show(Menu $menu): View
    {
        $menu->load(['documentFolders' => fn ($q) => $q->ordered()->withCount('documents')]);

        return view('menus.show', compact('menu'));
    }

    public function create(): View
    {
        $this->authorize('create', Menu::class);

        return view('menus.create', ['menu' => new Menu()]);
    }

    public function store(MenuRequest $request): RedirectResponse
    {
        $this->authorize('create', Menu::class);

        $menu = Menu::create($request->validated());

        return redirect()
            ->route('menus.show', $menu)
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
            ->route('menus.show', $menu)
            ->with('status', __('menus.updated'));
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $this->authorize('delete', $menu);

        $menu->delete();

        return redirect()->route('dashboard')->with('status', __('menus.deleted'));
    }
}
