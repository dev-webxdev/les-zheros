@extends('layouts.admin')

@section('title', 'Corbeille des annonces | Les Z-heros')
@section('description', 'Corbeille des annonces de la guilde Les Z-heros.')
@php($activeAdmin = 'admin-announcements')
@php($canForceDeleteAnnouncements = auth()->user()?->canForceDeleteInAdminArea('announcements'))

@section('admin')
@include('admin.partials.trash-page', [
    'breadcrumb' => 'Annonces / Corbeille',
    'backUrl' => route('admin.annonces.index'),
    'backLabel' => 'Retour aux annonces',
    'emptyTrashUrl' => route('admin.annonces.empty-trash'),
    'canEmptyTrash' => $canForceDeleteAnnouncements,
    'items' => $announcements,
    'titleIcon' => 'fa-solid fa-bullhorn',
    'tableView' => 'admin.partials.trash.announcements',
])
@endsection
