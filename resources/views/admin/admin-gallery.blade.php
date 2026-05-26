@extends('layouts.admin')

@section('title', 'Galerie | Les Zheros')
@section('description', 'Administration de la galerie de guilde Les Zheros.')
@php($activeAdmin = 'admin-gallery')
@push('scripts')
<script src="{{ asset('assets/js/admin-gallery.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    @component('admin.components.page-header', ['breadcrumb' => 'Galerie'])
        @slot('actions')
            <label class="admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Rechercher..." data-gallery-search>
            </label>
            @component('admin.components.button', ['href' => route('admin.galerie.trash'), 'class' => 'admin-secondary-button', 'icon' => 'fa-regular fa-trash-can', 'label' => 'Corbeille'])@endcomponent
            @component('admin.components.button', ['href' => route('admin.galerie.create'), 'class' => 'admin-create-button', 'icon' => 'fa-solid fa-circle-plus', 'label' => 'Ajouter'])@endcomponent
        @endslot
    @endcomponent

    <section class="admin-content">
        <div class="admin-title admin-title--split">
            <div><i class="fa-regular fa-images"></i><h1>Galerie</h1></div>
        </div>

        @include('admin.partials.bulk-actions', [
            'id' => 'gallery-bulk-form',
            'action' => route('admin.galerie.bulk'),
            'actions' => $canDeleteGallery ? ['trash' => 'Mettre en corbeille'] : [],
        ])

        <div class="admin-gallery-grid" data-gallery-list>
            @forelse($images as $image)
                <article class="admin-gallery-card" data-gallery-item data-title="{{ $image->title }}">
                    @if($canDeleteGallery)
                        <label class="admin-gallery-card__check">
                            <input type="checkbox" name="ids[]" value="{{ $image->id }}" form="gallery-bulk-form" data-bulk-item aria-label="Sélectionner {{ $image->title }}">
                        </label>
                    @endif
                    <button class="admin-gallery-card__media" type="button" data-gallery-preview="{{ $image->imageUrl() }}" data-gallery-title="{{ $image->title }}">
                        <img src="{{ $image->imageUrl() }}" alt="{{ $image->title }}" loading="lazy">
                    </button>
                    <div class="admin-gallery-card__body">
                        <div>
                            <h2>{{ $image->title }}</h2>
                            <p>{{ $image->description ? Str::limit($image->description, 220) : 'Aucune description ajoutée.' }}</p>
                        </div>
                        <div class="admin-gallery-card__meta">
                            @component('admin.components.badge', ['class' => $image->is_published ? 'admin-tag--success' : null, 'label' => $image->is_published ? 'Publié' : 'Brouillon'])@endcomponent
                            <time datetime="{{ $image->dateValue() }}">{{ $image->displayDate() }}</time>
                        </div>
                    </div>
                    <div class="admin-gallery-card__actions">
                        <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.galerie.edit', $image) }}" aria-label="Modifier {{ $image->title }}" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></a>
                        @if($canDeleteGallery)
                            <form action="{{ route('admin.galerie.destroy', $image) }}" method="post" data-real-form>
                                @csrf
                                @method('delete')
                                <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Mettre {{ $image->title }} à la corbeille" title="Corbeille"><i class="fa-regular fa-trash-can"></i></button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                @component('admin.components.empty-state', ['icon' => 'fa-regular fa-images', 'title' => 'Aucune image', 'text' => 'Ajoute une image pour commencer la galerie.'])@endcomponent
            @endforelse
        </div>
        @include('partials.admin-pagination', ['paginator' => $images])
    </section>
</main>
@endsection

@section('modals')
<div class="admin-proof-modal" data-gallery-modal hidden>
    <div class="admin-proof-modal__backdrop" data-gallery-close></div>
    <section class="admin-proof-modal__dialog" role="dialog" aria-modal="true" aria-label="Image galerie">
        <button class="admin-proof-modal__close" type="button" data-gallery-close aria-label="Fermer l'image"><i class="fa-solid fa-xmark"></i></button>
        <img src="{{ asset('assets/img/divers/hall-guilde.png') }}" alt="" data-gallery-modal-image>
    </section>
</div>
@endsection
