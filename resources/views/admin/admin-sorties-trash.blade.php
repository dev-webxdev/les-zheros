@extends('layouts.admin')

@section('title', 'Corbeille des sorties | Les Zheros')
@section('description', 'Corbeille des sorties de guilde Les Zheros.')
@php($activeAdmin = 'admin-sorties')
@php($canDeleteOutings = auth()->user()?->canDeleteInAdminArea('outings'))

@section('admin')
@include('admin.partials.trash-page', [
    'breadcrumb' => 'Sorties / Corbeille',
    'backUrl' => route('admin.sorties.index'),
    'backLabel' => 'Retour aux sorties',
    'emptyTrashUrl' => route('admin.sorties.empty-trash'),
    'items' => $outings,
    'canEmptyTrash' => $canDeleteOutings,
    'contentClass' => 'admin-outings',
    'titleIcon' => 'fa-solid fa-users',
    'tableView' => 'admin.partials.trash.sorties',
])
@endsection
