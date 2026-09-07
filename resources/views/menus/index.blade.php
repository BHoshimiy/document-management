@extends('layouts.app')
@section('title', __('app.standard'))

@section('content')
    <x-page-header
        :title="__('app.standard')"
        :subtitle="trans_choice('app.standard_count', $menus->total())">
        <x-slot:action>
            @can('create', \App\Models\Menu::class)
                <a href="{{ route('admin.menus.create') }}" class="btn-create">
                    <x-icon name="plus" /> {{ __('app.create') }}
                </a>
            @endcan
        </x-slot:action>
    </x-page-header>

    <div class="toolbar">
        <div></div>
        <x-search-box :action="route('admin.menus.index')" :placeholder="__('app.search_standards')" />
    </div>

    <div class="uploads-card mt-3">
        @if ($menus->isEmpty())
            <div class="uploads-empty">
                <div class="empty-title">{{ __('app.no_standards') }}</div>
            </div>
        @else
            <table class="uploads-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('app.name') }}</th>
                    <th>{{ __('app.folders') }}</th>
                    <th>{{ __('app.order') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($menus as $menu)
                    <tr>
                        <td class="row-num">{{ $loop->iteration + ($menus->currentPage() - 1) * $menus->perPage() }}</td>
                        <td>{{ $menu->name }}</td>
                        <td>{{ trans_choice('app.folder_count', $menu->document_folders_count) }}</td>
                        <td>{{ $menu->order }}</td>
                        <td>
                            <div class="d-flex gap-2 justify-content-end align-items-center">
                                <a href="{{ route('admin.menus.edit', $menu) }}" class="btn-pin">
                                    {{ __('app.edit') }}
                                </a>
                                <a href="{{ route('menus.show', $menu) }}" class="btn-open">{{ __('app.view') }}</a>
                                @can('delete', $menu)
                                    <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}"
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

    {{ $menus->links() }}
@endsection
