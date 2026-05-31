@extends('layouts.admin')

@section('title', 'Corbeille des roles | Les Zheros')
@section('description', 'Corbeille des roles de la guilde Les Zheros.')
@php($activeAdmin = 'admin-roles')
@php($canForceDeleteRoles = auth()->user()?->canForceDeleteInAdminArea('roles'))

@section('admin')
@include('admin.partials.trash-page', [
    'breadcrumb' => 'Roles / Corbeille',
    'backUrl' => route('admin.roles.index'),
    'backLabel' => 'Retour aux roles',
    'emptyTrashUrl' => route('admin.roles.empty-trash'),
    'canEmptyTrash' => $canForceDeleteRoles,
    'items' => $roles,
    'titleIcon' => 'fa-solid fa-shield-halved',
    'tableView' => 'admin.partials.trash.roles',
])
@endsection
