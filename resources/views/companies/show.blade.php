@extends('layouts.app')
@section('title', $company->name)

@section('content')
    <x-page-header
        :title="$company->name"
        :subtitle="'INN ' . $company->inn . ' · ' . trans_choice('app.document_count', $company->documents_count)"
        :back="route('admin.companies.index')"
        :backLabel="__('app.back_to_companies')" />

    <div class="row g-3 mt-1">
        <div class="col-12 col-lg-6">
            <x-doc-card :code="__('app.owner')" :title="$company->user?->username ?? '—'"
                        :meta="$company->address" />
        </div>
        <div class="col-12 col-lg-6">
            <x-doc-card :code="__('app.certificates')"
                        :title="(string) $company->certificates->count()" />
        </div>
    </div>
@endsection
