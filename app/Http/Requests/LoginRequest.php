<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /** Throttled credential check; returns the authenticated user. */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $user = User::where('username', $this->string('username'))->first();

        if (! $user || ! Hash::check($this->string('password'), $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        if (! $user->status->canLogin()) {
            throw ValidationException::withMessages([
                'username' => __('auth.inactive'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            throw ValidationException::withMessages([
                'username' => __('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($this->throttleKey()),
                ]),
            ]);
        }
    }

    protected function throttleKey(): string
    {
        return mb_strtolower($this->string('username')->toString()).'|'.$this->ip();
    }
}
