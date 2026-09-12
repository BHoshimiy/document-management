@extends('layouts.app')
@section('title', __('app.create_category'))

@section('content')
    <x-page-header
        :title="__('app.create_category')"
        :subtitle="$menu->name"
        :back="route('menus.show', $menu)"
        :backLabel="__('app.back_to', ['name' => $menu->name])" />

    @include('categories._form', [
        'action' => route('menus.categories.store', $menu),
        'cancel' => route('menus.show', $menu),
        'lockedMenu' => true,
    ])
@endsection
