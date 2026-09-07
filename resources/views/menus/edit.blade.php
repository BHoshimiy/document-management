@extends('layouts.app')
@section('title', __('app.edit_standard'))

@section('content')
    <x-page-header
        :title="__('app.edit_standard')"
        :subtitle="$menu->name"
        :back="route('admin.menus.index')"
        :backLabel="__('app.back_to_standards')" />

    @include('menus._form')
@endsection
