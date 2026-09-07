@extends('layouts.app')
@section('title', __('app.template_library'))

@section('content')
    <x-page-header
        :title="__('app.template_library')"
        :subtitle="trans_choice('app.folder_count', $menus->sum(fn ($m) => $m->documentFolders->count()))" />

    @forelse ($menus as $menu)
        @continue($menu->documentFolders->isEmpty())

        <div class="section-heading">{{ $menu->name }}</div>

        <div class="row g-3 mb-4">
            @foreach ($menu->documentFolders as $folder)
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
            @endforeach
        </div>
    @empty
        <div class="empty-state">
            <div class="glyph"><x-icon name="search" /></div>
            <div class="empty-title">{{ __('app.no_folders') }}</div>
        </div>
    @endforelse
@endsection
