@extends('layouts.admin')

@section('title', 'Corbeille des validations | Les Zheros')
@section('description', 'Corbeille des validations de missions de la guilde Les Zheros.')
@php($activeAdmin = 'admin-validations')
@php($canDeleteValidations = auth()->user()?->canDeleteInAdminArea('validations'))

@section('admin')
@include('admin.partials.trash-page', [
    'breadcrumb' => 'Validations / Corbeille',
    'backUrl' => route('admin.validations.index'),
    'backLabel' => 'Retour aux validations',
    'emptyTrashUrl' => route('admin.validations.empty-trash'),
    'items' => $validations,
    'canEmptyTrash' => $canDeleteValidations,
    'emptyButtonFirst' => true,
    'titleIcon' => 'fa-solid fa-circle-check',
    'tableView' => 'admin.partials.trash.validations',
])
@endsection
