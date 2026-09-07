@extends('layouts.app')
@section('title', __('app.create_folder'))

@section('content')
    <x-page-header
        :title="__('app.create_folder')"
        :back="route('admin.folders.index')"
        :backLabel="__('app.back_to_folders')" />

    @include('folders._form')
@endsection
