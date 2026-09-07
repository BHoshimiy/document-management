@extends('layouts.app')
@section('title', __('app.template_library'))

@section('content')
    <x-page-header
        :title="__('app.template_library')"
        :subtitle="trans_choice('app.folder_count', $menus->sum(fn ($m) => $m->documentFolders->count()))" />

    <div class="toolbar">
        <div>
            <label for="t-All" class="tab-pill" id="tp-All">All</label>
            <label for="t-Records" class="tab-pill" id="tp-Records">Records</label>
            <label for="t-SOPs" class="tab-pill" id="tp-SOPs">SOPs</label>
            <label for="t-Policy" class="tab-pill" id="tp-Policy">Policy</label>
            <label for="t-Hardware" class="tab-pill" id="tp-Hardware">Hardware</label>
            <label for="t-Photos" class="tab-pill" id="tp-Photos">Photos</label>
            <label for="t-Staff-files" class="tab-pill" id="tp-Staff-files">Staff files</label>
            <label for="t-Documents" class="tab-pill" id="tp-Documents">Documents</label>
        </div>
        <div class="search-box">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" placeholder="Search templates" disabled="" title="Search needs JavaScript, which this page doesn't use.">
        </div>
    </div>

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
