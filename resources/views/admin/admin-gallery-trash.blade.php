@extends('layouts.admin')

@section('title', 'Corbeille galerie | Les Zheros')
@section('description', 'Corbeille de la galerie de guilde Les Zheros.')
@php($activeAdmin = 'admin-gallery')
@php($canDeleteGallery = auth()->user()?->canDeleteInAdminArea('gallery'))

@section('admin')
@include('admin.partials.trash-page', [
    'breadcrumb' => 'Galerie / Corbeille',
    'backUrl' => route('admin.galerie.index'),
    'backLabel' => 'Retour a la galerie',
    'emptyTrashUrl' => route('admin.galerie.empty-trash'),
    'items' => $images,
    'titleIcon' => 'fa-regular fa-trash-can',
    'titleText' => 'Corbeille galerie',
    'canEmptyTrash' => $canDeleteGallery,
    'bulk' => [
        'id' => 'gallery-trash-bulk-form',
        'action' => route('admin.galerie.bulk'),
        'actions' => array_filter([
            'restore' => 'Restaurer',
            $canDeleteGallery ? 'force_delete' : null => $canDeleteGallery ? 'Supprimer définitivement' : null,
        ]),
    ],
    'tableView' => 'admin.partials.trash.gallery',
])
@endsection
