@extends('layouts.app')
@section('title', __('app.create_category'))

@section('content')
    <x-page-header
        :title="__('app.create_category')"
        :subtitle="__('app.create_category_sub')"
        :back="route('admin.categories.index')"
        :backLabel="__('app.back_to_categories')" />

    @include('categories._form')
@endsection
