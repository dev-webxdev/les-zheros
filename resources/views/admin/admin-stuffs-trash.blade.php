@extends('layouts.admin')

@section('title', 'Corbeille stuffs | Les Z-heros')
@section('description', 'Corbeille du catalogue de stuffs Les Z-heros.')
@php($activeAdmin = 'admin-stuffs')

@section('admin')
@include('admin.partials.trash-page', [
    'breadcrumb' => 'Catalogue stuffs / Corbeille',
    'backUrl' => route('admin.stuffs.index'),
    'backLabel' => 'Retour au catalogue',
    'emptyTrashUrl' => route('admin.stuffs.empty-trash'),
    'items' => $stuffs,
    'contentClass' => 'admin-stuffs',
    'titleIcon' => 'fa-regular fa-trash-can',
    'titleText' => 'Corbeille stuffs',
    'tableView' => 'admin.partials.trash.stuffs',
])
@endsection
