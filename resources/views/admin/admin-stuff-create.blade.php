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
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Catalogue stuffs / {{ $isEdit ? 'Modifier' : 'Créer' }}</p>
        </div>

        <div class="admin-actions">
            <a class="admin-secondary-button" href="{{ route('admin.stuffs.index') }}">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Retour au catalogue</span>
            </a>
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-solid fa-shield-halved"></i>
            <h1>{{ $isEdit ? 'Modifier un stuff' : 'Créer un stuff' }}</h1>
        </div>

        <section class="admin-form-card" aria-labelledby="stuff-form-title">
            <div class="admin-form-head">
                <div>
                    <h2 id="stuff-form-title">Informations du stuff</h2>
                    <p>Ajoute un build au catalogue avec son lien Dofusbook et ses conditions.</p>
                </div>
            </div>

            <form class="admin-mission-form" action="{{ $isEdit ? route('admin.stuffs.update', $stuff) : route('admin.stuffs.store') }}" method="post" data-real-form>
                @csrf
                @if($isEdit)
                    @method('patch')
                @endif

                <section class="admin-form-section">
                    <div class="admin-form-section-title">
                        <span>1</span>
                        <div>
                            <h3>Build</h3>
                            <p>Renseigne le lien et les informations principales du stuff.</p>
                        </div>
                    </div>

                    <div class="admin-stuff-form-grid">
                        <label class="admin-field" for="stuff-title">
                            <span>Titre</span>
                            <input id="stuff-title" name="title" type="text" value="{{ old('title', $stuff->title) }}" placeholder="Ex: Feu/Air burst" required>
                        </label>

                        <label class="admin-field" for="stuff-dofusbook">
                            <span>URL Dofusbook</span>
                            <input id="stuff-dofusbook" name="dofusbook_url" type="url" value="{{ old('dofusbook_url', $stuff->dofusbook_url) }}" placeholder="https://www.dofusbook.net/..." required>
                        </label>

                        <label class="admin-field" for="stuff-class">
                            <span>Classe</span>
                            <select id="stuff-class" name="class" required>
                                <option value="">Choisir une classe</option>
                                @foreach(Stuff::CLASSES as $label)
                                    <option @selected(old('class', $stuff->class_label) === $label)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="admin-field admin-stuff-element-picker" data-admin-stuff-elements>
                            <span>Éléments</span>
                            <input id="stuff-element" name="element" type="hidden" value="{{ old('element', $selectedElements) }}" required>
                            <div class="admin-stuff-element-picker__choices">
                                @foreach(Stuff::ELEMENTS as $element)
                                    <button type="button" data-stuff-admin-element="{{ $element }}">{{ $element }}</button>
                                @endforeach
                            </div>
                        </div>

                        <label class="admin-field" for="stuff-mode">
                            <span>Mode</span>
                            <select id="stuff-mode" name="mode" required>
                                <option value="">Choisir un mode</option>
                                @foreach(Stuff::MODES as $mode)
                                    <option @selected(old('mode', $stuff->mode) === $mode)>{{ $mode }}</option>
                                @endforeach
                            </select>
                        </label>

                    </div>
                </section>

                <section class="admin-form-section">
                    <div class="admin-form-section-title">
                        <span>2</span>
                        <div>
                            <h3>Conditions</h3>
                            <p>Précise les niveaux et les remarques utiles.</p>
                        </div>
                    </div>

                    <div class="admin-stuff-form-grid">
                        <label class="admin-field" for="stuff-meta">
                            <span>Meta optionnel</span>
                            <input id="stuff-meta" name="meta" type="text" value="{{ old('meta', $stuff->meta) }}" placeholder="ex: 3.5">
                        </label>

                        <label class="admin-field" for="stuff-min-level">
                            <span>Niveau min</span>
                            <select id="stuff-min-level" name="min_level" required>
                                @foreach($stuffLevels as $level)
                                    <option value="{{ $level }}" @selected((int) old('min_level', $stuff->min_level ?: 200) === $level)>{{ $level }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="admin-field" for="stuff-max-level">
                            <span>Niveau max</span>
                            <select id="stuff-max-level" name="max_level">
                                @foreach($stuffLevels as $level)
                                    <option value="{{ $level }}" @selected((int) old('max_level', $stuff->max_level ?: 200) === $level)>{{ $level }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="admin-field admin-field--full" for="stuff-comment">
                            <span>Commentaire</span>
                            <textarea id="stuff-comment" name="comment" placeholder="Pour quel type de joueur, variantes, points forts/faibles...">{{ old('comment', $stuff->description) }}</textarea>
                        </label>

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
                </section>

                <div class="admin-form-actions">
                    <a class="admin-secondary-button" href="{{ route('admin.stuffs.index') }}">
                        <i class="fa-solid fa-xmark"></i>
                        <span>Annuler</span>
                    </a>
                    <button class="admin-create-button" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Enregistrer</span>
                    </button>
                </div>
            </form>
        </section>
    </section>
</main>
@endsection
