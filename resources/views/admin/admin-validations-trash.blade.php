@extends('layouts.admin')

@section('title', 'Corbeille des validations | Les Zheros')
@section('description', 'Corbeille des validations de missions de la guilde Les Zheros.')
@php($activeAdmin = 'admin-validations')
@php($canForceDeleteValidations = auth()->user()?->canForceDeleteInAdminArea('validations'))

@section('admin')
@include('admin.partials.trash-page', [
    'breadcrumb' => 'Validations / Corbeille',
    'backUrl' => route('admin.validations.index'),
    'backLabel' => 'Retour aux validations',
    'emptyTrashUrl' => route('admin.validations.empty-trash'),
    'items' => $validations,
    'canEmptyTrash' => $canForceDeleteValidations,
    'emptyButtonFirst' => true,
    'titleIcon' => 'fa-solid fa-circle-check',
    'bulk' => [
        'id' => 'validations-trash-bulk-form',
        'action' => route('admin.validations.bulk'),
        'actions' => array_filter([
            'restore' => 'Restaurer',
            $canForceDeleteValidations ? 'force_delete' : null => $canForceDeleteValidations ? 'Supprimer définitivement' : null,
        ]),
    ],
    'tableView' => 'admin.partials.trash.validations',
])
@endsection
