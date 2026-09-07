@extends('layouts.app')
@section('title', __('app.edit_category'))

@section('content')
    <x-page-header
        :title="__('app.edit_category')"
        :subtitle="$category->name"
        :back="route('admin.categories.index')"
        :backLabel="__('app.back_to_categories')" />

    @include('categories._form')
@endsection
