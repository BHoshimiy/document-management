@extends('layouts.app')
@section('title', __('app.create_standard'))

@section('content')
    <x-page-header
        :title="__('app.create_standard')"
        :back="route('admin.menus.index')"
        :backLabel="__('app.back_to_standards')" />

    @include('menus._form')
@endsection
