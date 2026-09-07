@if (session('status'))
    <div class="flash flash-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="flash flash-error">
        @if ($errors->count() === 1)
            {{ $errors->first() }}
        @else
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
