<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('document_folder')?->id;

        return [
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64'],
            'slug' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('document_folders', 'slug')
                    ->ignore($id)
                    ->where('menu_id', $this->integer('menu_id'))
                    ->whereNull('deleted_at'),
            ],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => $this->slug ?: Str::slug((string) $this->input('name.en'))]);
    }
}
