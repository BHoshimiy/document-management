<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Models\DocumentFolder;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CategoryController extends Controller implements HasMiddleware
{
    /**
     * Resource-wide authorization, the same ability map authorizeResource()
     * used to register from the constructor. Laravel dropped controller
     * instance middleware, so it is declared here. `reorder` is not a resource
     * ability and authorizes inline.
     *
     * @return list<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,'.Category::class, only: ['index']),
            new Middleware('can:view,category', only: ['show']),
            new Middleware('can:create,'.Category::class, only: ['create', 'store']),
            new Middleware('can:update,category', only: ['edit', 'update']),
            new Middleware('can:delete,category', only: ['destroy']),
        ];
    }

    public function __construct(private readonly CategoryService $categories) {}

    public function index(Request $request): View
    {
        $categories = Category::query()
            ->when($request->string('search')->toString(),
                fn ($q, $term) => $q->whereTranslationLike('name', $term))
            ->ordered()
            ->with('documentFolder')
            ->withCount('documents')
            ->paginate(24)
            ->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create', [
            'category' => new Category,
            'folders' => DocumentFolder::with(['menu'])->ordered()->get(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', __('categories.created'));
    }

    public function show(Category $category): RedirectResponse
    {
        return redirect()->route('admin.categories.edit', $category);
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', [
            'category' => $category,
            'folders' => DocumentFolder::ordered()->get(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('status', __('categories.updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->documents()->exists()) {
            return back()->withErrors(['category' => __('categories.has_documents')]);
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('status', __('categories.deleted'));
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->authorize('reorder', Category::class);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $this->categories->reorder($validated['ids']);

        return back()->with('status', __('categories.reordered'));
    }
}
