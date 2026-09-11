@extends('layouts.app')
@section('title', $menu->name)

@section('content')
    <x-page-header
        :title="$menu->name"
        :subtitle="trans_choice('app.folder_count', $folders->count())"
        :back="route('dashboard')"
        :backLabel="__('app.back_to_library')" />

    <div class="toolbar">
        <div>
            <a href="{{ route('menus.show', ['menu' => $menu, 'search' => $search ?: null]) }}"
               class="tab-pill @unless ($activeCategory) active @endunless">{{ __('app.all') }}</a>
            @foreach ($categories as $category)
                <a href="{{ route('menus.show', ['menu' => $menu, 'category' => $category->slug, 'search' => $search ?: null]) }}"
                   class="tab-pill @if ($activeCategory?->is($category)) active @endif">{{ $category->name }}</a>
            @endforeach
        </div>
        <x-search-box
            :action="route('menus.show', $menu)"
            :placeholder="__('app.search_folders')"
            :hidden="['category' => $activeCategory?->slug]" />
    </div>

    <div class="row g-3">
        @forelse ($folders as $folder)
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
