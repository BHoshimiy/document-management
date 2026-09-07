<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DocumentFolderRequest;
use App\Models\Category;
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
        $folder->load('menu', 'categories');

        $documents = Document::query()
            ->visibleTo($request->user())
            ->where('document_folder_id', $folder->id)
            ->with('category', 'company')
            ->latest()
            ->paginate(25);

        $categories = Category::query()
            ->where(fn ($q) => $q->whereNull('document_folder_id')->orWhere('document_folder_id', $folder->id))
            ->ordered()
            ->get();

        return view('folders.show', compact('folder', 'documents', 'categories'));
    }

    /* ---------------- admin CRUD ---------------- */

    public function index(): View
    {
        $this->authorize('viewAny', DocumentFolder::class);

        $folders = DocumentFolder::query()
            ->with('menu')
            ->withCount('documents')
            ->ordered()
            ->paginate(24);

        return view('folders.index', compact('folders'));
    }

    public function create(): View
    {
        $this->authorize('create', DocumentFolder::class);

        return view('folders.create', [
            'folder' => new DocumentFolder(),
            'menus' => Menu::ordered()->get(),
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
            'menus' => Menu::ordered()->get(),
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
