@props(['placeholder' => null, 'action', 'hidden' => []])

<form method="GET" action="{{ $action }}" class="search-box">
    @foreach ($hidden as $field => $value)
        @if (filled($value))
            <input type="hidden" name="{{ $field }}" value="{{ $value }}">
        @endif
    @endforeach
    <x-icon name="search" />
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="{{ $placeholder ?? __('app.search') }}">
</form>
