@extends('layouts.app')
@section('title', __('app.create_folder'))

@section('content')
    <x-page-header
        :title="__('app.create_folder')"
        :subtitle="$menu->name"
        :back="route('menus.show', $menu)"
        :backLabel="__('app.back_to', ['name' => $menu->name])" />

    @include('folders._form', [
        'action' => route('menus.folders.store', $menu),
        'cancel' => route('menus.show', $menu),
        'lockedMenu' => true,
    ])
@endsection
