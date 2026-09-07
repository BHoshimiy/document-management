@extends('layouts.guest')
@section('title', __('auth.sign_in'))

@section('content')
    <div class="auth-title">{{ __('auth.sign_in') }}</div>
    <div class="auth-sub">{{ __('auth.sign_in_sub') }}</div>

    <x-alert />

    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="username">{{ __('auth.username') }}</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}"
                   class="form-control @error('username') is-invalid @enderror"
                   required autofocus autocomplete="username">
            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">{{ __('auth.password') }}</label>
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="auth-row">
            <label class="auth-check">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                {{ __('auth.remember_me') }}
            </label>
        </div>

        <button type="submit" class="btn-auth">{{ __('auth.sign_in') }}</button>
    </form>

    <div class="auth-foot">
        {{ __('auth.no_account') }} <a href="{{ route('register') }}">{{ __('auth.create_one') }}</a>
    </div>
@endsection
