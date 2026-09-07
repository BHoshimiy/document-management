<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'username' => $request->string('username'),
                'password' => $request->string('password'),
                'role' => UserRole::Client,
                'status' => UserStatus::Active,
            ]);

            Company::create([
                'user_id' => $user->id,
                'name' => $request->input('company.name'),
                'inn' => $request->input('company.inn'),
                'address' => $request->input('company.address'),
                'logo' => $request->file('company.logo')?->store('companies/logos', 'public'),
            ]);

            return $user;
        });

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
