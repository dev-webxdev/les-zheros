@extends('layouts.admin')

@section('title', 'Corbeille des utilisateurs | Les Zheros')
@section('description', 'Corbeille des utilisateurs de la guilde Les Zheros.')
@php($activeAdmin = 'admin-users')
@php($canForceDeleteUsers = auth()->user()?->canForceDeleteInAdminArea('users'))

@section('admin')
@include('admin.partials.trash-page', [
    'breadcrumb' => 'Utilisateurs / Corbeille',
    'backUrl' => route('admin.utilisateurs.index'),
    'backLabel' => 'Retour aux utilisateurs',
    'emptyTrashUrl' => route('admin.utilisateurs.empty-trash'),
    'items' => $users,
    'canEmptyTrash' => $canForceDeleteUsers,
    'emptyButtonFirst' => true,
    'titleIcon' => 'fa-solid fa-users',
    'bulk' => [
        'id' => 'users-trash-bulk-form',
        'action' => route('admin.utilisateurs.bulk'),
        'actions' => array_filter([
            'restore' => 'Restaurer',
            $canForceDeleteUsers ? 'force_delete' : null => $canForceDeleteUsers ? 'Supprimer définitivement' : null,
        ]),
    ],
    'tableView' => 'admin.partials.trash.users',
])
@endsection
