<div class="form-card mt-3">
    <form method="POST" action="{{ route('admin.companies.update', $company) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label" for="name">{{ __('app.company_name') }}</label>
            <input type="text" id="name" name="name" value="{{ old('name', $company->name) }}"
                   class="form-control @error('name') is-invalid @enderror" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="inn">{{ __('app.inn') }}</label>
            <input type="text" id="inn" name="inn" value="{{ old('inn', $company->inn) }}"
                   class="form-control @error('inn') is-invalid @enderror" required>
            @error('inn') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="address">{{ __('app.address') }}</label>
            <input type="text" id="address" name="address" value="{{ old('address', $company->address) }}"
                   class="form-control @error('address') is-invalid @enderror">
            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="logo">{{ __('app.logo') }}</label>
            @if ($company->logo_url)
                <div class="mb-2"><img src="{{ $company->logo_url }}" alt="" class="logo-preview"></div>
            @endif
            <input type="file" id="logo" name="logo" accept="image/*"
                   class="form-control @error('logo') is-invalid @enderror">
            @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-actions">
            <a href="{{ route('admin.companies.index') }}" class="btn-pin">{{ __('app.cancel') }}</a>
            <button type="submit" class="btn-open">{{ __('app.save_company') }}</button>
        </div>
    </form>
</div>
