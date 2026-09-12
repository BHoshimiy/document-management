@extends('layouts.app')
@section('title', $menu->name)

@section('content')
    <x-page-header
            :title="$menu->name"
            :menu="$menu"
            :active-category="$activeCategory"
            :subtitle="trans_choice('app.folder_count', $folders->count())"
            :back="route('dashboard')"
            :backLabel="__('app.back_to_library')"/>

    <div class="toolbar">
        <div>
            <a href="{{ route('menus.show', ['menu' => $menu, 'search' => $search ?: null]) }}"
               class="tab-pill @unless ($activeCategory) active @endunless">{{ __('app.all') }}</a>
            @foreach ($categories as $category)
                <a href="{{ route('menus.show', ['menu' => $menu, 'category' => $category->slug, 'search' => $search ?: null]) }}"
                   class="tab-pill @if ($activeCategory?->is($category)) active @endif">{{ $category->name }}</a>
            @endforeach
            @can('create', \App\Models\Category::class)
                <a href="{{ route('menus.categories.create', $menu) }}" class="tab-pill tab-pill-add">
                    <x-icon name="plus"/> {{ __('app.create_category') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="row g-3">
        @foreach($folders as $folder)
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

        @can('create', \App\Models\DocumentFolder::class)
            <div class="col-12 col-sm-6 col-lg-4">
                <a class="add-card"
                   href="{{ route('menus.folders.create', ['menu' => $menu, 'category_id' => $activeCategory?->id]) }}">
                    <span class="glyph"><x-icon name="plus"/></span>
                    {{ __('app.create_folder') }}
                </a>
            </div>
        @endcan
    </div>
@endsection
