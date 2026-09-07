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

    <div class="uploads-card mt-3">
        @if ($categories->isEmpty())
            <div class="uploads-empty">
                <div class="empty-title">{{ __('app.no_categories') }}</div>
            </div>
        @else
            <table class="uploads-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('app.name') }}</th>
                    <th>{{ __('app.document_folder') }}</th>
                    <th>{{ __('app.documents') }}</th>
                    <th>{{ __('app.order') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($categories as $category)
                    <tr>
                        <td class="row-num">{{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}</td>
                        <td>
                            {{ $category->name }}
                            @if ($category->is_default)
                                <span class="badge-soft badge-ready">{{ __('app.default') }}</span>
                            @endif
                        </td>
                        <td>{{ $category->documentFolder?->name ?? __('app.global_category') }}</td>
                        <td>{{ trans_choice('app.document_count', $category->documents_count) }}</td>
                        <td>{{ $category->order }}</td>
                        <td>
                            <div class="d-flex gap-2 justify-content-end align-items-center">
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
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{ $categories->links() }}
@endsection
