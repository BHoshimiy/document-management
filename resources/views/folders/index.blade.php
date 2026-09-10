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

    <div class="uploads-card mt-3">
        @if ($folders->isEmpty())
            <div class="uploads-empty">
                <div class="empty-title">{{ __('app.no_folders') }}</div>
            </div>
        @else
            <table class="uploads-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('app.code') }}</th>
                    <th>{{ __('app.name') }}</th>
                    <th>{{ __('app.standard') }}</th>
                    <th>{{ __('app.category') }}</th>
                    <th>{{ __('app.documents') }}</th>
                    <th>{{ __('app.order') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($folders as $folder)
                    <tr>
                        <td class="row-num">{{ $loop->iteration + ($folders->currentPage() - 1) * $folders->perPage() }}</td>
                        <td><span class="type-chip">{{ $folder->code }}</span></td>
                        <td>{{ $folder->name }}</td>
                        <td>{{ $folder->menu?->name }}</td>
                        <td>{{ $folder->category?->name ?? '—' }}</td>
                        <td>{{ trans_choice('app.document_count', $folder->documents_count) }}</td>
                        <td>{{ $folder->order }}</td>
                        <td>
                            <div class="d-flex gap-2 justify-content-end align-items-center">
                                <a href="{{ route('admin.folders.edit', $folder) }}" class="btn-pin">
                                    {{ __('app.edit') }}
                                </a>
                                <a href="{{ route('folders.show', $folder) }}" class="btn-open">{{ __('app.view') }}</a>
                                @can('delete', $folder)
                                    <form method="POST" action="{{ route('admin.folders.destroy', $folder) }}"
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

    {{ $folders->links() }}
@endsection
