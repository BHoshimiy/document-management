<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // company ownership is checked in the controller
    }

    public function rules(): array
    {
        return [
            // Required only for catalog managers; a client always uploads to
            // their own company and any posted value is ignored.
            'company_id' => [
                Rule::requiredIf(fn () => $this->user()->canManageCatalog()),
                'integer',
                Rule::exists('companies', 'id')->whereNull('deleted_at'),
            ],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png'],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_id' => __('documents.company'),
            'category_id' => __('documents.category'),
            'file' => __('documents.file'),
        ];
    }
}
