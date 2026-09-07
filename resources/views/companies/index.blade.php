@extends('layouts.app')
@section('title', __('app.companies'))

@section('content')
    <x-page-header :title="__('app.companies')"
                   :subtitle="trans_choice('app.company_count', $companies->total())" />

    <div class="toolbar">
        <div></div>
        <x-search-box :action="route('admin.companies.index')" :placeholder="__('app.search_companies')" />
    </div>

    <div class="row g-3">
        @forelse ($companies as $company)
            <div class="col-12 col-sm-6 col-lg-4">
                <x-doc-card
                    :code="'INN ' . $company->inn"
                    :title="$company->name"
                    :meta="trans_choice('app.document_count', $company->documents_count)">
                    <x-slot:actions>
                        <span class="text-muted-sm">{{ $company->user?->username }}</span>
                        <a href="{{ route('admin.companies.show', $company) }}" class="btn-open">{{ __('app.view') }}</a>
                    </x-slot:actions>
                </x-doc-card>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state">
                    <div class="glyph"><x-icon name="search" /></div>
                    <div class="empty-title">{{ __('app.no_companies') }}</div>
                </div>
            </div>
        @endforelse
    </div>

    {{ $companies->links() }}
@endsection
