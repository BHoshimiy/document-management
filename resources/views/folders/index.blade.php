@extends('layouts.app')
@section('title', __('app.document_folder'))

@section('content')
    <x-page-header
        :title="__('app.document_folder')"
        :subtitle="trans_choice('app.folder_count', $folders->total())">
        <x-slot:action>
            @can('create', \App\Models\DocumentFolder::class)
                <a href="{{ route('admin.folders.create') }}" class="btn-create">
                    <x-icon name="plus" /> {{ __('app.create') }}
                </a>
            @endcan
        </x-slot:action>
    </x-page-header>

    <div class="row g-3">
        @forelse ($folders as $folder)
            <div class="col-12 col-sm-6 col-lg-4">
                <x-doc-card
                    :code="$folder->code"
                    :title="$folder->name"
                    :meta="$folder->menu?->name . ' · ' . trans_choice('app.document_count', $folder->documents_count)">
                    <x-slot:actions>
                        <a href="{{ route('admin.folders.edit', $folder) }}" class="btn-pin">{{ __('app.edit') }}</a>
                        <a href="{{ route('folders.show', $folder) }}" class="btn-open">{{ __('app.view') }}</a>
                    </x-slot:actions>
                </x-doc-card>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state">
                    <div class="glyph"><x-icon name="search" /></div>
                    <div class="empty-title">{{ __('app.no_folders') }}</div>
                </div>
            </div>
        @endforelse
    </div>

    {{ $folders->links() }}
@endsection
