@props(['placeholder' => null, 'action'])

<form method="GET" action="{{ $action }}" class="search-box">
    <x-icon name="search" />
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="{{ $placeholder ?? __('app.search') }}">
</form>
