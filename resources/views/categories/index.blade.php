@extends('layouts.app')
@section('title', __('app.category'))

@section('content')
    <x-page-header
        :title="__('app.category')"
        :subtitle="trans_choice('app.category_count', $categories->total())">
        <x-slot:action>
            @can('create', \App\Models\Category::class)
                <a href="{{ route('admin.categories.create') }}" class="btn-create">
                    <x-icon name="plus" /> {{ __('app.create') }}
                </a>
            @endcan
        </x-slot:action>
    </x-page-header>

    <div class="toolbar">
        <div></div>
        <x-search-box :action="route('admin.categories.index')" :placeholder="__('app.search_categories')" />
    </div>

    <div class="row g-3">
        @forelse ($categories as $category)
            <div class="col-12 col-sm-6 col-lg-4">
                <x-doc-card
                    :code="__('app.category')"
                    :title="$category->name"
                    :meta="$category->documentFolder?->name ?? __('app.global_category')"
                    :badges="$category->is_default ? [__('app.default') => 'badge-ready'] : []">
                    <x-slot:actions>
                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn-pin">
                            {{ __('app.edit') }}
                        </a>
                        @can('delete', $category)
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                  onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-pin btn-pin-danger">{{ __('app.delete') }}</button>
                            </form>
                        @endcan
                    </x-slot:actions>
                </x-doc-card>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state">
                    <div class="glyph"><x-icon name="search" /></div>
                    <div class="empty-title">{{ __('app.no_categories') }}</div>
                </div>
            </div>
        @endforelse
    </div>

    {{ $categories->links() }}
@endsection
