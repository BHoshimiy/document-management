@php
    $isEdit = $folder->exists;
    $translations = $folder->exists ? $folder->getTranslations('name') : [];
@endphp

<div class="form-card mt-3">
    <form method="POST"
          action="{{ $isEdit ? route('admin.folders.update', $folder) : route('admin.folders.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="mb-3">
            <label class="form-label" for="menu_id">{{ __('app.standard') }}</label>
            <select id="menu_id" name="menu_id" class="form-select @error('menu_id') is-invalid @enderror" required>
                <option value="">{{ __('app.select_standard') }}</option>
                @foreach ($menus as $menu)
                    <option value="{{ $menu->id }}" @selected(old('menu_id', $folder->menu_id) == $menu->id)>
                        {{ $menu->name }}
                    </option>
                @endforeach
            </select>
            @error('menu_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

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
            <label class="form-label" for="code">{{ __('app.code') }}</label>
            <input type="text" id="code" name="code" value="{{ old('code', $folder->code) }}"
                   class="form-control @error('code') is-invalid @enderror"
                   placeholder="RP-FER-01" required>
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="order">{{ __('app.order') }}</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $folder->order) }}"
                   class="form-control @error('order') is-invalid @enderror">
            @error('order') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-actions">
            <a href="{{ route('admin.folders.index') }}" class="btn-pin">{{ __('app.cancel') }}</a>
            <button type="submit" class="btn-open">
                {{ $isEdit ? __('app.save_changes') : __('app.save_folder') }}
            </button>
        </div>
    </form>
</div>
