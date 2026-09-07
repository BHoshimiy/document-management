<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::query()
            ->unless($request->user()->canManageCatalog(),
                fn ($q) => $q->where('user_id', $request->user()->id))
            ->when($request->string('search')->toString(), fn ($q, $term) => $q->where(
                fn ($sub) => $sub->where('name', 'like', "%{$term}%")->orWhere('inn', 'like', "%{$term}%")
            ))
            ->withCount('documents')
            ->with('user')
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('companies.index', compact('companies'));
    }

    public function show(Company $company): View
    {
        $this->authorize('view', $company);

        $company->load('user', 'certificates')->loadCount('documents');

        return view('companies.show', compact('company'));
    }
}
