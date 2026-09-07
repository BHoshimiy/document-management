@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => null])

@if ($back)
    <a href="{{ $back }}" class="cat-back"><x-icon name="back" /> {{ $backLabel ?? __('app.back') }}</a>
@endif

<div class="page-header-row">
    <div>
        <div class="page-title">{{ $title }}</div>
        @if ($subtitle)
            <div class="page-sub">{{ $subtitle }}</div>
        @endif
    </div>

    @isset($action)
        <div>{{ $action }}</div>
    @endisset
</div>
