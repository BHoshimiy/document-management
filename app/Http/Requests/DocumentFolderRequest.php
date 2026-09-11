<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\DocumentFolder;
use App\Support\Slug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at')
                        ->where(fn ($q) => $q
                            ->where(fn ($global) => $global->whereNull('menu_id')->where('is_default', true))
                            ->orWhere('menu_id', $this->integer('menu_id')));
                }),
            ],
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64'],
            'slug' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** The folder's category is optional; when set it must be a global default or belong to the chosen menu. */
    public function messages(): array
    {
        return ['category_id.exists' => __('folders.category_menu_mismatch')];
    }

    /**
     * The slug is system-generated from the English name, never posted. It has to
     * be unique table-wide — the folder page is routed as folders/{folder:slug}.
     * The route parameter is `folder`, renamed in routes/web.php.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Slug::uniqueFor(
                DocumentFolder::withTrashed(),
                (string) $this->input('name.en'),
                $this->route('folder')?->id,
            ),
        ]);
    }
}
