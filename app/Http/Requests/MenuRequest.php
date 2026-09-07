<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Menu;
use App\Support\Slug;
use Illuminate\Foundation\Http\FormRequest;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** The slug is system-generated from the English name, never posted. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Slug::uniqueFor(
                Menu::withTrashed(),
                (string) $this->input('name.en'),
                $this->route('menu')?->id,
            ),
        ]);
    }
}
