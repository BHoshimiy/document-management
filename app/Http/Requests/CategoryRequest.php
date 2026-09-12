<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Menu;
use App\Support\Slug;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // handled by the policy on the controller
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['boolean'],
            'menu_id' => ['nullable', 'integer', 'exists:menus,id'],
        ];
    }

    /** The slug is system-generated from the English name, never posted. */
    protected function prepareForValidation(): void
    {
        // The menu-scoped create route fixes the menu; the admin form posts it.
        $menu = $this->route('menu');

        $this->merge([
            'menu_id' => $menu instanceof Menu ? $menu->id : $this->input('menu_id'),
            'slug' => Slug::uniqueFor(
                Category::withTrashed(),
                (string) $this->input('name.en'),
                $this->route('category')?->id,
            ),
            'is_default' => $this->boolean('is_default'),
        ]);
    }
}
