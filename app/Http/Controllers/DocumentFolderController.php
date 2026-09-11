<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DocumentFolderRequest;
use App\Models\Category;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentFolderController extends Controller
{
    /** Client-facing folder page: the uploaded-documents table. */
    public function show(Request $request, DocumentFolder $folder): View
    {
        $folder->load('menu');

        $documents = Document::query()
            ->visibleTo($request->user())
            ->where('document_folder_id', $folder->id)
            ->with('category', 'company')
            ->latest()
            ->paginate(25);

        $categories = Category::query()
            ->forMenu($folder->menu_id)
            ->ordered()
            ->get();

        // Catalog managers upload on a company's behalf and need to pick one;
        // a client always uploads to their own.
        $companies = $request->user()->canManageCatalog()
            ? Company::query()->orderBy('name')->get()
            : collect();

        return view('folders.show', compact('folder', 'documents', 'categories', 'companies'));
    }

    /** Create a folder from inside the menu page, returning there afterwards. */
    public function createInMenu(Request $request, Menu $menu): View
    {
        $this->authorize('create', DocumentFolder::class);

        $folder = new DocumentFolder;
        $folder->menu_id = $menu->id;
        $folder->category_id = $request->integer('category_id') ?: null;

        return view('folders.create-in-menu', [
            'folder' => $folder,
            'menu' => $menu,
            // A one-menu collection, so _form's optgroups offer only this menu's
            // categories alongside the global defaults.
            'menus' => collect([$menu->load('categories')]),
            'globalCategories' => Category::query()->whereNull('menu_id')->defaults()->ordered()->get(),
        ]);
    }

    public function storeInMenu(DocumentFolderRequest $request, Menu $menu): RedirectResponse
    {
        $this->authorize('create', DocumentFolder::class);

        DocumentFolder::create($request->validated());

        // Back to the unfiltered menu page, so the new folder is visible
        // whichever category it landed in.
        return redirect()
            ->route('menus.show', $menu)
            ->with('status', __('folders.created'));
    }

    /* ---------------- admin CRUD ---------------- */

    public function index(): View
    {
        $this->authorize('viewAny', DocumentFolder::class);

        $folders = DocumentFolder::query()
            ->with('menu', 'category')
            ->withCount('documents')
            ->ordered()
            ->paginate(24);

        return view('folders.index', compact('folders'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', DocumentFolder::class);

        // The menu page's create tile links here with the menu, and the active
        // category pill, already chosen.
        $folder = new DocumentFolder;
        $folder->menu_id = $request->integer('menu_id') ?: null;
        $folder->category_id = $request->integer('category_id') ?: null;

        return view('folders.create', [
            'folder' => $folder,
            'menus' => Menu::query()->with('categories')->ordered()->get(),
            'globalCategories' => Category::query()->whereNull('menu_id')->defaults()->ordered()->get(),
        ]);
    }

    public function store(DocumentFolderRequest $request): RedirectResponse
    {
        $this->authorize('create', DocumentFolder::class);

        DocumentFolder::create($request->validated());

        return redirect()
            ->route('admin.folders.index')
            ->with('status', __('folders.created'));
    }

    public function edit(DocumentFolder $folder): View
    {
        $this->authorize('update', $folder);

        return view('folders.edit', [
            'folder' => $folder,
            'menus' => Menu::query()->with('categories')->ordered()->get(),
            'globalCategories' => Category::query()->whereNull('menu_id')->defaults()->ordered()->get(),
        ]);
    }

    public function update(DocumentFolderRequest $request, DocumentFolder $folder): RedirectResponse
    {
        $this->authorize('update', $folder);

        $folder->update($request->validated());

        return redirect()
            ->route('admin.folders.index')
            ->with('status', __('folders.updated'));
    }

    public function destroy(DocumentFolder $folder): RedirectResponse
    {
        $this->authorize('delete', $folder);

        if ($folder->documents()->exists()) {
            return back()->withErrors(['folder' => __('folders.has_documents')]);
        }

        $folder->delete();

        return redirect()
            ->route('admin.folders.index')
            ->with('status', __('folders.deleted'));
    }
}
