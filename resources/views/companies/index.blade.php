@extends('layouts.app')
@section('title', __('app.companies'))

@section('content')
    <x-page-header :title="__('app.companies')"
                   :subtitle="trans_choice('app.company_count', $companies->total())" />

    <div class="toolbar">
        <div></div>
        <x-search-box :action="route('admin.companies.index')" :placeholder="__('app.search_companies')" />
    </div>

    <div class="uploads-card mt-3">
        @if ($companies->isEmpty())
            <div class="uploads-empty">
                <div class="empty-title">{{ __('app.no_companies') }}</div>
            </div>
        @else
            <table class="uploads-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('app.name') }}</th>
                    <th>{{ __('app.inn') }}</th>
                    <th>{{ __('app.owner') }}</th>
                    <th>{{ __('app.documents') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($companies as $company)
                    <tr>
                        <td class="row-num">{{ $loop->iteration + ($companies->currentPage() - 1) * $companies->perPage() }}</td>
                        <td>{{ $company->name }}</td>
                        <td>{{ $company->inn }}</td>
                        <td>{{ $company->user?->username }}</td>
                        <td>{{ trans_choice('app.document_count', $company->documents_count) }}</td>
                        <td>
                            <div class="d-flex gap-2 justify-content-end align-items-center">
                                @can('update', $company)
                                    <a href="{{ route('admin.companies.edit', $company) }}" class="btn-pin">
                                        {{ __('app.edit') }}
                                    </a>
                                @endcan
                                <a href="{{ route('admin.companies.show', $company) }}" class="btn-open">
                                    {{ __('app.view') }}
                                </a>
                                @can('delete', $company)
                                    <form method="POST" action="{{ route('admin.companies.destroy', $company) }}"
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

    {{ $companies->links() }}
@endsection
