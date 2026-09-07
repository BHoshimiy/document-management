@extends('layouts.guest')
@section('title', __('auth.create_account'))

@section('content')
    <div class="auth-title">{{ __('auth.create_account') }}</div>
    <div class="auth-sub">{{ __('auth.create_account_sub') }}</div>

    <x-alert />

    <form method="POST" action="{{ route('register') }}" class="auth-form" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="username">{{ __('auth.username') }}</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}"
                   class="form-control @error('username') is-invalid @enderror"
                   required autofocus autocomplete="username">
            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="company_name">{{ __('auth.company_name') }}</label>
            <input type="text" id="company_name" name="company[name]" value="{{ old('company.name') }}"
                   class="form-control @error('company.name') is-invalid @enderror" required>
            @error('company.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="company_inn">{{ __('auth.inn') }}</label>
            <input type="text" id="company_inn" name="company[inn]" value="{{ old('company.inn') }}"
                   class="form-control @error('company.inn') is-invalid @enderror" required>
            @error('company.inn') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="company_address">{{ __('auth.address') }}</label>
            <input type="text" id="company_address" name="company[address]" value="{{ old('company.address') }}"
                   class="form-control @error('company.address') is-invalid @enderror">
            @error('company.address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">{{ __('auth.password') }}</label>
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="new-password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="password_confirmation">{{ __('auth.confirm_password') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="form-control" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn-auth">{{ __('auth.create_account') }}</button>
    </form>

    <div class="auth-foot">
        {{ __('auth.have_account') }} <a href="{{ route('login') }}">{{ __('auth.sign_in') }}</a>
    </div>
@endsection
