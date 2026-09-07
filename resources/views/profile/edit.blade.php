@extends('layouts.app')
@section('title', __('app.profile'))

@section('content')
    <x-page-header :title="__('app.profile')" :subtitle="__('app.profile_sub')" />

    <div class="row g-3 mt-2">
        <div class="col-12 col-lg-6">
            <x-doc-card :code="__('app.account')" :title="$user->username"
                        :meta="__('roles.' . $user->role->value) . ' · ' . __('statuses.' . $user->status->value)" />
        </div>
        <div class="col-12 col-lg-6">
            <x-doc-card :code="__('app.standards')"
                        :title="$sidebarMenus->map->name->join(' · ') ?: '—'" />
        </div>
    </div>

    @if ($company)
        <div class="form-card mt-4">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf @method('PUT')

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
                    <button type="submit" class="btn-open">{{ __('app.save_changes') }}</button>
                </div>
            </form>
        </div>

        @if ($company->certificates->isNotEmpty())
            <div class="section-heading mt-4">{{ __('app.certificates') }}</div>
            <div class="uploads-card">
                <table class="uploads-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('app.valid_from') }}</th>
                        <th>{{ __('app.valid_to') }}</th>
                        <th>{{ __('app.status') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($company->certificates as $certificate)
                        <tr>
                            <td class="row-num">{{ $loop->iteration }}</td>
                            <td>{{ $certificate->date_from?->translatedFormat('d M Y') }}</td>
                            <td>{{ $certificate->date_to?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td>
                                <span class="badge-soft {{ $certificate->is_expired ? 'badge-fixed' : 'badge-ready' }}">
                                    {{ $certificate->is_expired ? __('app.expired') : __('app.valid') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
@endsection
