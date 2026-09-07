<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    public function edit(Company $company): View
    {
        $this->authorize('update', $company);

        return view('companies.edit', compact('company'));
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            Storage::disk('public')->delete((string) $company->logo);
            $data['logo'] = $request->file('logo')->store('companies/logos', 'public');
        }

        $company->update($data);

        return redirect()
            ->route('admin.companies.index')
            ->with('status', __('companies.updated'));
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        if ($company->documents()->exists()) {
            return back()->withErrors(['company' => __('companies.has_documents')]);
        }

        // Soft delete keeps the logo file, matching how DocumentService::delete works.
        $company->delete();

        return redirect()
            ->route('admin.companies.index')
            ->with('status', __('companies.deleted'));
    }
}
