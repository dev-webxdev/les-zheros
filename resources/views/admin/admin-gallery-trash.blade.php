@extends('layouts.admin')

@section('title', 'Corbeille galerie | Les Zheros')
@section('description', 'Corbeille de la galerie de guilde Les Zheros.')
@php($activeAdmin = 'admin-gallery')

@section('admin')
@include('admin.partials.trash-page', [
    'breadcrumb' => 'Galerie / Corbeille',
    'backUrl' => route('admin.galerie.index'),
    'backLabel' => 'Retour a la galerie',
    'emptyTrashUrl' => route('admin.galerie.empty-trash'),
    'items' => $images,
    'titleIcon' => 'fa-regular fa-trash-can',
    'titleText' => 'Corbeille galerie',
    'tableView' => 'admin.partials.trash.gallery',
])
@endsection
