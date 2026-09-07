<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load('company.certificates');

        return view('profile.edit', [
            'user' => $user,
            'company' => $user->company,
        ]);
    }

    public function update(CompanyRequest $request): RedirectResponse
    {
        $company = $request->user()->company;

        abort_if($company === null, 404);
        $this->authorize('update', $company);

        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            Storage::disk('public')->delete((string) $company->logo);
            $data['logo'] = $request->file('logo')->store('companies/logos', 'public');
        }

        $company->update($data);

        return back()->with('status', __('profile.updated'));
    }
}
