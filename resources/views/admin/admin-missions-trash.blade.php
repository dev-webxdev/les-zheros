@extends('layouts.admin')

@section('title', 'Corbeille des missions | Les Zheros')
@section('description', 'Corbeille des missions de la guilde Les Zheros.')
@php($activeAdmin = 'admin-missions')
@php($canDeleteMissions = auth()->user()?->canDeleteInAdminArea('missions'))

@section('admin')
@include('admin.partials.trash-page', [
    'breadcrumb' => 'Missions / Corbeille',
    'backUrl' => route('admin.missions.index'),
    'backLabel' => 'Retour aux missions',
    'emptyTrashUrl' => route('admin.missions.empty-trash'),
    'items' => $missions,
    'canEmptyTrash' => $canDeleteMissions,
    'titleIcon' => 'fa-solid fa-scroll',
    'tableView' => 'admin.partials.trash.missions',
])
@endsection
