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
        $id = $this->route('company')?->id;

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
