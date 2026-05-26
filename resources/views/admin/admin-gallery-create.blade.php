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
    @component('admin.components.page-header', ['breadcrumb' => 'Galerie / '.($isEdit ? 'Modifier' : 'Ajouter')])
        @slot('actions')
            @component('admin.components.button', ['href' => route('admin.galerie.index'), 'class' => 'admin-secondary-button', 'icon' => 'fa-solid fa-arrow-left', 'label' => 'Retour à la galerie'])@endcomponent
        @endslot
    @endcomponent

    <section class="admin-content admin-content--editor">
        <form id="gallery-form" class="admin-gallery-editor admin-gallery-editor--cms" action="{{ $isEdit ? route('admin.galerie.update', $image) : route('admin.galerie.store') }}" method="post" enctype="multipart/form-data" data-real-form>
            @csrf
            @if($isEdit)
                @method('patch')
            @endif

            <section class="admin-gallery-compose">
                @component('admin.components.text-input', [
                    'id' => 'gallery-title',
                    'name' => 'title',
                    'label' => 'Titre de l\'image',
                    'value' => old('title', $image->title),
                    'placeholder' => 'Ex: Sortie Frigost',
                    'required' => true,
                    'baseClass' => false,
                    'class' => 'admin-guide-title-field',
                ])@endcomponent

                @component('admin.components.textarea', [
                    'id' => 'gallery-description',
                    'name' => 'description',
                    'label' => 'Description',
                    'value' => old('description', $image->description),
                    'placeholder' => 'Contexte, participants, souvenir...',
                    'baseClass' => false,
                    'class' => 'admin-guide-summary admin-gallery-description',
                ])@endcomponent
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
                    @component('admin.components.text-input', [
                        'id' => 'gallery-url',
                        'name' => 'image_url',
                        'type' => 'url',
                        'label' => 'URL image',
                        'value' => old('image_url'),
                        'placeholder' => 'https://...',
                        'inputAttributes' => 'data-gallery-url',
                    ])@endcomponent

                    @component('admin.components.text-input', [
                        'id' => 'gallery-date',
                        'name' => 'taken_at',
                        'type' => 'date',
                        'label' => 'Date',
                        'value' => old('taken_at', $image->taken_at?->toDateString()),
                    ])@endcomponent
                </section>

                @component('admin.components.button', ['type' => 'submit', 'class' => 'admin-create-button admin-guide-save-button', 'icon' => 'fa-solid fa-floppy-disk', 'label' => 'Enregistrer'])@endcomponent
            </aside>
        </form>
    </section>
</main>
@endsection
