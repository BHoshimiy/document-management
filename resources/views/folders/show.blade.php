@extends('layouts.app')
@section('title', $folder->name)

@section('content')
    <x-page-header
        :title="$folder->name"
        :subtitle="$folder->code . ' · ' . trans_choice('app.document_count', $documents->total())"
        :back="route('menus.show', $folder->menu)"
        :backLabel="__('app.back_to', ['name' => $folder->menu->name])" />

    @php
        $ownCompany = auth()->user()->company;
        $canManage = auth()->user()->canManageCatalog();
    @endphp

    @if ($ownCompany || ($canManage && $companies->isNotEmpty()))
        <div class="upload-card">
            <form method="POST" action="{{ route('documents.store', $folder) }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-12 {{ $canManage ? 'col-md-4' : 'col-md-5' }}">
                        <label class="form-label" for="file">{{ __('app.choose_file') }}</label>
                        <input type="file" id="file" name="file"
                               class="form-control @error('file') is-invalid @enderror" required>
                        @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @if ($canManage)
                        <div class="col-12 col-md-3">
                            <label class="form-label" for="company_id">{{ __('app.company') }}</label>
                            <select id="company_id" name="company_id"
                                    class="form-select @error('company_id') is-invalid @enderror" required>
                                <option value="">{{ __('app.select_company') }}</option>
                                @foreach ($companies as $option)
                                    <option value="{{ $option->id }}" @selected(old('company_id') == $option->id)>
                                        {{ $option->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    @endif
                    <div class="col-12 {{ $canManage ? 'col-md-3' : 'col-md-4' }}">
                        <label class="form-label" for="category_id">{{ __('app.category') }}</label>
                        <select id="category_id" name="category_id"
                                class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">{{ __('app.select_category') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 {{ $canManage ? 'col-md-2' : 'col-md-3' }}">
                        <button style="margin-bottom: 5px" type="submit" class="btn-open w-100">{{ __('app.upload') }}</button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="uploads-card mt-3">
        @if ($documents->isEmpty())
            <div class="uploads-empty">
                <div class="empty-title">{{ __('app.no_documents') }}</div>
                <div class="empty-sub">{{ __('app.no_documents_sub') }}</div>
            </div>
        @else
            <table class="uploads-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('app.name') }}</th>
                    <th>{{ __('app.category') }}</th>
                    <th>{{ __('app.uploaded_date') }}</th>
                    <th>{{ __('app.type') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($documents as $document)
                    <tr>
                        <td class="row-num">{{ $loop->iteration + ($documents->currentPage() - 1) * $documents->perPage() }}</td>
                        <td>
                            <div class="file-name">
                                {{ $document->name }}
                            </div>
                        </td>
                        <td>{{ $document->category?->name }}</td>
                        <td>{{ $document->created_at->translatedFormat('d M Y') }}</td>
                        <td><span class="type-chip">{{ $document->extension }}</span></td>
                        <td class="text-end">
                            <a class="btn-download" href="{{ route('documents.download', $document) }}">
                                <x-icon name="download" /> {{ __('app.download') }}
                            </a>
                            @can('delete', $document)
                                <form method="POST" action="{{ route('documents.destroy', $document) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-danger-link">{{ __('app.delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{ $documents->links() }}
@endsection
