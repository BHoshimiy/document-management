<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** The template library — every folder across every standard. */
    public function index(): View
    {
        $menus = Menu::query()
            ->ordered()
            ->with(['documentFolders' => fn ($q) => $q->ordered()->withCount('documents')])
            ->get();

        $categories = Category::query()->ordered()->get();

        return view('dashboard.index', compact('menus', 'categories'));
    }
}
