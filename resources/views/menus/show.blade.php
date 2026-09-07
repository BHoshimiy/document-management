@extends('layouts.app')
@section('title', $menu->name)

@section('content')
    <x-page-header
        :title="$menu->name"
        :subtitle="trans_choice('app.folder_count', $menu->documentFolders->count())"
        :back="route('dashboard')"
        :backLabel="__('app.back_to_library')" />

    <div class="row g-3">
        @forelse ($menu->documentFolders as $folder)
            <div class="col-12 col-sm-6 col-lg-4">
                <x-doc-card
                    :code="$folder->code"
                    :title="$folder->name"
                    :meta="trans_choice('app.document_count', $folder->documents_count)">
                    <x-slot:actions>
                        <span></span>
                        <a href="{{ route('folders.show', $folder) }}" class="btn-open">{{ __('app.open') }}</a>
                    </x-slot:actions>
                </x-doc-card>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state">
                    <div class="glyph"><x-icon name="search" /></div>
                    <div class="empty-title">{{ __('app.no_folders') }}</div>
                    <div class="empty-sub">{{ __('app.no_folders_sub') }}</div>
                </div>
            </div>
        @endforelse
    </div>
@endsection
