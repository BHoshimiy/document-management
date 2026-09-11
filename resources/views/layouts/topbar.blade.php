<header class="topbar">
    <div class="title">@yield('title', __('app.document_manager'))</div>

    <div class="tools">
        <div class="seg">
            @foreach (config('app.supported_locales', ['en', 'ru']) as $locale)
                <a href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}"
                   class="{{ app()->getLocale() === $locale ? 'active' : '' }}">
                    {{ strtoupper($locale) }}
                </a>
            @endforeach
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-pin">{{ __('app.sign_out') }}</button>
        </form>
    </div>
</header>
