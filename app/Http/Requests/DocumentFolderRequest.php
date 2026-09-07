<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\DocumentFolder;
use App\Support\Slug;
use Illuminate\Foundation\Http\FormRequest;

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
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64'],
            'slug' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * The slug is system-generated from the English name, never posted. It only
     * has to be unique within its menu — the table is unique on (menu_id, slug).
     * The route parameter is `folder`, renamed in routes/web.php.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Slug::uniqueFor(
                DocumentFolder::withTrashed()->where('menu_id', $this->integer('menu_id')),
                (string) $this->input('name.en'),
                $this->route('folder')?->id,
            ),
        ]);
    }
}
