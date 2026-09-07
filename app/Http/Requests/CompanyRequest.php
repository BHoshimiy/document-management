<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // admin edits bind {company}; the profile route has no parameter, so fall
        // back to the signed-in user's own company. company() (not ->company) keeps
        // this off the lazy-loading path that Model::shouldBeStrict() forbids.
        $id = $this->route('company')->id ?? $this->user()?->company()->value('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'inn' => [
                'required', 'string', 'max:32',
                Rule::unique('companies', 'inn')->ignore($id)->whereNull('deleted_at'),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
