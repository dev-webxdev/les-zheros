@extends('layouts.admin')

@php
    $activeAdmin = 'admin-gallery';
    $isEdit = $image->exists;
    $preview = old('image_url', $image->image_path);
@endphp

@section('title', ($isEdit ? 'Modifier' : 'Ajouter').' une image | Les Z-héros')
@section('description', 'Ajout et modification d\'une image à la galerie de guilde Les Z-héros.')
@push('scripts')
<script src="{{ asset('assets/js/admin-gallery.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation"><i class="fa-solid fa-table-columns"></i></button>
            <span></span>
            <p>Galerie / {{ $isEdit ? 'Modifier' : 'Ajouter' }}</p>
        </div>
        <div class="admin-actions">
            <a class="admin-secondary-button" href="{{ route('admin.galerie.index') }}"><i class="fa-solid fa-arrow-left"></i><span>Retour à la galerie</span></a>
        </div>
    </header>

    <section class="admin-content admin-content--editor">
        <form id="gallery-form" class="admin-gallery-editor admin-gallery-editor--cms" action="{{ $isEdit ? route('admin.galerie.update', $image) : route('admin.galerie.store') }}" method="post" enctype="multipart/form-data" data-real-form>
            @csrf
            @if($isEdit)
                @method('patch')
            @endif

            <section class="admin-gallery-compose">
                <label class="admin-guide-title-field" for="gallery-title">
                    <span>Titre de l'image</span>
                    <input id="gallery-title" name="title" type="text" value="{{ old('title', $image->title) }}" placeholder="Ex: Sortie Frigost" required>
                </label>

                <label class="admin-guide-summary admin-gallery-description" for="gallery-description">
                    <span>Description</span>
                    <textarea id="gallery-description" name="description" placeholder="Contexte, participants, souvenir...">{{ old('description', $image->description) }}</textarea>
                </label>
            </section>

            <aside class="admin-gallery-editor__aside admin-gallery-editor__aside--cms" data-gallery-form>
                <section class="admin-guide-side-card">
                    <div class="admin-guide-switch-row">
                        <span>Publié</span>
                        <label class="admin-switch">
                            <input type="checkbox" name="published" @checked(old('published', $image->is_published ?? true))>
                            <span></span>
                        </label>
                    </div>
                </section>

                <section class="admin-guide-side-card admin-guide-side-card--cover">
                    <label class="admin-cover-picker" for="gallery-file">
                        <span class="admin-gallery-empty-preview" data-gallery-empty-preview @if($preview) hidden @endif>
                            <i class="fa-regular fa-image"></i>
                            <strong>Aucune image</strong>
                            <small>Upload une image ou colle une URL.</small>
                        </span>
                        <img class="admin-cover-preview" src="{{ $preview ?: '' }}" alt="" data-gallery-preview-image @if(!$preview) hidden @endif>
                        <span>Aperçu</span>
                    </label>
                    <input id="gallery-file" class="admin-cover-input" name="image" type="file" accept="image/*" data-gallery-file>
                </section>

                <section class="admin-guide-side-card">
                    <label class="admin-field" for="gallery-url">
                        <span>URL image</span>
                        <input id="gallery-url" name="image_url" type="url" value="{{ old('image_url') }}" placeholder="https://..." data-gallery-url>
                    </label>
                    <label class="admin-field" for="gallery-date">
                        <span>Date</span>
                        <input id="gallery-date" name="taken_at" type="date" value="{{ old('taken_at', $image->taken_at?->toDateString()) }}">
                    </label>
                </section>

                <button class="admin-create-button admin-guide-save-button" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Enregistrer</span>
                </button>
            </aside>
        </form>
    </section>
</main>
@endsection
