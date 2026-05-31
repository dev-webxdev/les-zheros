@extends('layouts.admin')

@section('title', 'Corbeille des sorties | Les Zheros')
@section('description', 'Corbeille des sorties de guilde Les Zheros.')
@php($activeAdmin = 'admin-sorties')
@php($canForceDeleteOutings = auth()->user()?->canForceDeleteInAdminArea('outings'))

@section('admin')
@include('admin.partials.trash-page', [
    'breadcrumb' => 'Sorties / Corbeille',
    'backUrl' => route('admin.sorties.index'),
    'backLabel' => 'Retour aux sorties',
    'emptyTrashUrl' => route('admin.sorties.empty-trash'),
    'items' => $outings,
    'canEmptyTrash' => $canForceDeleteOutings,
    'contentClass' => 'admin-outings',
    'titleIcon' => 'fa-solid fa-users',
    'tableView' => 'admin.partials.trash.sorties',
])
@endsection
