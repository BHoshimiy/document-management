@php
    $isEdit = $category->exists;
    $translations = $category->exists ? $category->getTranslations('name') : [];
    $action ??= $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store');
    $cancel ??= route('admin.categories.index');
    $lockedMenu ??= false;
@endphp

<div class="form-card mt-3">
    <form method="POST" action="{{ $action }}">
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

        {{-- The menu page locks the menu and lets CategoryService assign the order;
             is_default only matters to global categories, so it stays admin-only. --}}
        @unless ($lockedMenu)
            <div class="mb-3">
                <label class="form-label" for="menu_id">{{ __('app.standard') }}</label>
                <select id="menu_id" name="menu_id"
                        class="form-select @error('menu_id') is-invalid @enderror">
                    <option value="">{{ __('app.global_category') }}</option>
                    @foreach ($menus as $menu)
                        <option value="{{ $menu->id }}"
                            @selected(old('menu_id', $category->menu_id) == $menu->id)>
                            {{ $menu->name }}
                        </option>
                    @endforeach
                </select>
                @error('menu_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="order">{{ __('app.order') }}</label>
                <input type="number" id="order" name="order" min="0"
                       value="{{ old('order', $category->order) }}"
                       class="form-control @error('order') is-invalid @enderror">
                @error('order') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3 form-check">
                <input type="hidden" name="is_default" value="0">
                <input type="checkbox" id="is_default" name="is_default" value="1"
                       class="form-check-input" @checked(old('is_default', $category->is_default))>
                <label class="form-check-label" for="is_default">{{ __('app.is_default') }}</label>
            </div>
        @endunless

        <div class="form-actions">
            <a href="{{ $cancel }}" class="btn-pin">{{ __('app.cancel') }}</a>
            <button type="submit" class="btn-open">
                {{ $isEdit ? __('app.save_changes') : __('app.save_category') }}
            </button>
        </div>
    </form>
</div>
