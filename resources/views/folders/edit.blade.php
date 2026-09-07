@extends('layouts.app')
@section('title', __('app.edit_folder'))

@section('content')
    <x-page-header
        :title="__('app.edit_folder')"
        :subtitle="$folder->name"
        :back="route('admin.folders.index')"
        :backLabel="__('app.back_to_folders')" />

    @include('folders._form')
@endsection
