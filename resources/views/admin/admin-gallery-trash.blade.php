@extends('layouts.admin')

@section('title', 'Corbeille galerie | Les Zheros')
@section('description', 'Corbeille de la galerie de guilde Les Zheros.')
@php($activeAdmin = 'admin-gallery')
@php($canForceDeleteGallery = auth()->user()?->canForceDeleteInAdminArea('gallery'))

@section('admin')
@include('admin.partials.trash-page', [
    'breadcrumb' => 'Galerie / Corbeille',
    'backUrl' => route('admin.galerie.index'),
    'backLabel' => 'Retour a la galerie',
    'emptyTrashUrl' => route('admin.galerie.empty-trash'),
    'items' => $images,
    'titleIcon' => 'fa-regular fa-trash-can',
    'titleText' => 'Corbeille galerie',
    'canEmptyTrash' => $canForceDeleteGallery,
    'bulk' => [
        'id' => 'gallery-trash-bulk-form',
        'action' => route('admin.galerie.bulk'),
        'actions' => array_filter([
            'restore' => 'Restaurer',
            $canForceDeleteGallery ? 'force_delete' : null => $canForceDeleteGallery ? 'Supprimer définitivement' : null,
        ]),
    ],
    'tableView' => 'admin.partials.trash.gallery',
])
@endsection
