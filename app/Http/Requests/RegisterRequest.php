<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::defaults()],

            // company created together with the account
            'company.name' => ['required', 'string', 'max:255'],
            'company.inn' => ['required', 'string', 'max:32', 'unique:companies,inn'],
            'company.address' => ['nullable', 'string', 'max:500'],
            'company.logo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
