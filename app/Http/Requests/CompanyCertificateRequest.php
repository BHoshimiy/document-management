<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'certificate' => [$this->isMethod('POST') ? 'required' : 'nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'date_from' => ['required', 'date'],
            'date_to' => ['nullable', 'date', 'after:date_from'],
        ];
    }
}
