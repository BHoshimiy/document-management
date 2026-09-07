@php
    $isEdit = $menu->exists;
    $translations = $menu->exists ? $menu->getTranslations('name') : [];
@endphp

<div class="form-card mt-3">
    <form method="POST"
          action="{{ $isEdit ? route('admin.menus.update', $menu) : route('admin.menus.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="mb-3">
            <label class="form-label" for="name_en">{{ __('app.name_en') }}</label>
            <input type="text" id="name_en" name="name[en]"
                   value="{{ old('name.en', $translations['en'] ?? '') }}"
                   class="form-control @error('name.en') is-invalid @enderror" required>
            @error('name.en') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="name_ru">{{ __('app.name_ru') }}</label>
            <input type="text" id="name_ru" name="name[ru]"
                   value="{{ old('name.ru', $translations['ru'] ?? '') }}"
                   class="form-control @error('name.ru') is-invalid @enderror" required>
            @error('name.ru') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="order">{{ __('app.order') }}</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $menu->order) }}"
                   class="form-control @error('order') is-invalid @enderror">
            @error('order') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-actions">
            <a href="{{ route('admin.menus.index') }}" class="btn-pin">{{ __('app.cancel') }}</a>
            <button type="submit" class="btn-open">
                {{ $isEdit ? __('app.save_changes') : __('app.save_standard') }}
            </button>
        </div>
    </form>
</div>
