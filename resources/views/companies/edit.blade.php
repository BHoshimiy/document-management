@extends('layouts.app')
@section('title', __('app.edit_company'))

@section('content')
    <x-page-header
        :title="__('app.edit_company')"
        :subtitle="$company->name"
        :back="route('admin.companies.index')"
        :backLabel="__('app.back_to_companies')" />

    @include('companies._form')
@endsection
