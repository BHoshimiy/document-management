@props(['code' => null, 'title', 'meta' => null, 'badges' => []])

<div class="doc-card">
    <div>
        @if ($code)<div class="doc-code">{{ $code }}</div>@endif
        <div class="doc-title">{{ $title }}</div>
        @if ($meta)<div class="doc-meta">{{ $meta }}</div>@endif
        @foreach ($badges as $badge => $class)
            <span class="badge-soft {{ $class }}">{{ $badge }}</span>
        @endforeach
    </div>

    @isset($actions)
        <div class="card-actions">{{ $actions }}</div>
    @endisset
</div>
