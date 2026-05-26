@extends('layouts.admin')

@php
    use App\Models\Stuff;

    $isEdit = $stuff->exists;
    $activeAdmin = 'admin-stuffs';
    $selectedElements = collect($stuff->elements ?? [])->join('/');
    $stuffLevels = Stuff::LEVELS;
@endphp

@section('title', ($isEdit ? 'Modifier' : 'Créer').' un stuff | Les Z-héros')
@section('description', 'Création et modification d\'un stuff pour le catalogue Les Z-héros.')
@push('scripts')
<script src="{{ asset('assets/js/admin-stuffs.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    @component('admin.components.page-header', ['breadcrumb' => 'Catalogue stuffs / '.($isEdit ? 'Modifier' : 'Créer')])
        @slot('actions')
            @component('admin.components.button', ['href' => route('admin.stuffs.index'), 'class' => 'admin-secondary-button', 'icon' => 'fa-solid fa-arrow-left', 'label' => 'Retour au catalogue'])@endcomponent
        @endslot
    @endcomponent

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-shield-halved"></i>
            <h1>{{ $isEdit ? 'Modifier un stuff' : 'Créer un stuff' }}</h1>
        </div>

        @component('admin.components.form-card', [
            'titleId' => 'stuff-form-title',
            'title' => 'Informations du stuff',
            'description' => 'Ajoute un build au catalogue avec son lien Dofusbook et ses conditions.',
        ])
            <form class="admin-mission-form" action="{{ $isEdit ? route('admin.stuffs.update', $stuff) : route('admin.stuffs.store') }}" method="post" data-real-form>
                @csrf
                @if($isEdit)
                    @method('patch')
                @endif

                @component('admin.components.form-section', [
                    'number' => 1,
                    'title' => 'Build',
                    'description' => 'Renseigne le lien et les informations principales du stuff.',
                ])
                    <div class="admin-stuff-form-grid">
                        @component('admin.components.text-input', [
                            'id' => 'stuff-title',
                            'name' => 'title',
                            'label' => 'Titre',
                            'value' => old('title', $stuff->title),
                            'placeholder' => 'Ex: Feu/Air burst',
                            'required' => true,
                        ])@endcomponent

                        @component('admin.components.text-input', [
                            'id' => 'stuff-dofusbook',
                            'name' => 'dofusbook_url',
                            'type' => 'url',
                            'label' => 'URL Dofusbook',
                            'value' => old('dofusbook_url', $stuff->dofusbook_url),
                            'placeholder' => 'https://www.dofusbook.net/...',
                            'required' => true,
                        ])@endcomponent

                        @component('admin.components.select', [
                            'id' => 'stuff-class',
                            'name' => 'class',
                            'label' => 'Classe',
                            'required' => true,
                        ])
                            <option value="">Choisir une classe</option>
                            @foreach(Stuff::CLASSES as $label)
                                <option @selected(old('class', $stuff->class_label) === $label)>{{ $label }}</option>
                            @endforeach
                        @endcomponent

                        <div class="admin-field admin-stuff-element-picker" data-admin-stuff-elements>
                            <span>Éléments</span>
                            <input id="stuff-element" name="element" type="hidden" value="{{ old('element', $selectedElements) }}" required>
                            <div class="admin-stuff-element-picker__choices">
                                @foreach(Stuff::ELEMENTS as $element)
                                    <button type="button" data-stuff-admin-element="{{ $element }}">{{ $element }}</button>
                                @endforeach
                            </div>
                        </div>

                        @component('admin.components.select', [
                            'id' => 'stuff-mode',
                            'name' => 'mode',
                            'label' => 'Mode',
                            'required' => true,
                        ])
                            <option value="">Choisir un mode</option>
                            @foreach(Stuff::MODES as $mode)
                                <option @selected(old('mode', $stuff->mode) === $mode)>{{ $mode }}</option>
                            @endforeach
                        @endcomponent
                    </div>
                @endcomponent

                @component('admin.components.form-section', [
                    'number' => 2,
                    'title' => 'Conditions',
                    'description' => 'Précise les niveaux et les remarques utiles.',
                ])
                    <div class="admin-stuff-form-grid">
                        @component('admin.components.text-input', [
                            'id' => 'stuff-meta',
                            'name' => 'meta',
                            'label' => 'Meta optionnel',
                            'value' => old('meta', $stuff->meta),
                            'placeholder' => 'ex: 3.5',
                        ])@endcomponent

                        @component('admin.components.select', [
                            'id' => 'stuff-min-level',
                            'name' => 'min_level',
                            'label' => 'Niveau min',
                            'required' => true,
                        ])
                            @foreach($stuffLevels as $level)
                                <option value="{{ $level }}" @selected((int) old('min_level', $stuff->min_level ?: 200) === $level)>{{ $level }}</option>
                            @endforeach
                        @endcomponent

                        @component('admin.components.select', [
                            'id' => 'stuff-max-level',
                            'name' => 'max_level',
                            'label' => 'Niveau max',
                        ])
                            @foreach($stuffLevels as $level)
                                <option value="{{ $level }}" @selected((int) old('max_level', $stuff->max_level ?: 200) === $level)>{{ $level }}</option>
                            @endforeach
                        @endcomponent

                        @component('admin.components.textarea', [
                            'id' => 'stuff-comment',
                            'name' => 'comment',
                            'label' => 'Commentaire',
                            'value' => old('comment', $stuff->description),
                            'placeholder' => 'Pour quel type de joueur, variantes, points forts/faibles...',
                            'class' => 'admin-field--full',
                        ])@endcomponent

                        <label class="admin-check-field" for="stuff-highlight">
                            <input id="stuff-highlight" name="is_featured" type="checkbox" @checked(old('is_featured', $stuff->is_featured))>
                            <span>Mettre en avant ce build</span>
                        </label>

                        <section class="admin-guide-side-card admin-stuff-publish-card">
                            <div class="admin-guide-switch-row">
                                <span>Publié</span>
                                <label class="admin-switch" for="stuff-published">
                                    <input id="stuff-published" name="published" type="checkbox" @checked(old('published', $stuff->is_published ?? true))>
                                    <span></span>
                                </label>
                            </div>
                        </section>
                    </div>
                @endcomponent

                @component('admin.components.form-actions')
                    @component('admin.components.button', ['href' => route('admin.stuffs.index'), 'class' => 'admin-secondary-button', 'icon' => 'fa-solid fa-xmark', 'label' => 'Annuler'])@endcomponent
                    @component('admin.components.button', ['type' => 'submit', 'class' => 'admin-create-button', 'icon' => 'fa-solid fa-floppy-disk', 'label' => 'Enregistrer'])@endcomponent
                @endcomponent
            </form>
        @endcomponent
    </section>
</main>
@endsection
