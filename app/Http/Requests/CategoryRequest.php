<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // handled by the policy on the controller
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id;

        return [
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($id)->whereNull('deleted_at'),
            ],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['boolean'],
            'document_folder_id' => ['nullable', 'integer', 'exists:document_folders,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->slug ?: Str::slug((string) $this->input('name.en')),
            'is_default' => $this->boolean('is_default'),
        ]);
    }
}
