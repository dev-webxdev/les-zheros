@extends('layouts.admin')

@section('title', 'Modifier une mission | Les Zheros')
@section('description', 'Administration du site de la guilde Les Zheros.')
@php
    $activeAdmin = 'admin-missions';
    $missionCategoryBadges = [
    'donjon' => asset('assets/img/card-mission/type.png'),
    'regulation' => asset('assets/img/card-mission/regulation.png'),
    'expedition' => asset('assets/img/card-mission/expedition.png'),
    'anomalie' => asset('assets/img/card-mission/anomalie.png'),
    'songe' => asset('assets/img/card-mission/songe.png'),
    ];
@endphp
@php
    $missionImagePath = old('selected_image', $mission->image_path);
    $missionImageMode = old('image_mode', $mission->image_mode ?: null);

    if (! $missionImageMode && is_string($missionImagePath)) {
        $missionImageMode = str_starts_with($missionImagePath, 'assets/uploads/missions/')
            ? 'upload'
            : (str_starts_with($missionImagePath, 'http') ? 'url' : 'api');
    }

    if ($missionImageMode === 'api' && is_string($missionImagePath) && str_starts_with($missionImagePath, 'assets/uploads/missions/')) {
        $missionImageMode = 'upload';
    }

    $missionImageMode ??= 'api';
@endphp
@push('scripts')
<script src="{{ asset('assets/js/admin-missions.js') }}?v={{ filemtime(public_path('assets/js/admin-missions.js')) }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
            <header class="admin-topbar">
                <div class="admin-breadcrumb">
                    <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                        <i class="fa-solid fa-table-columns"></i>
                    </button>
                    <span></span>
                    <p>Missions / Modifier</p>
                </div>

                <div class="admin-actions">
                    <a class="admin-secondary-button" href="{{ route('admin.missions.index') }}">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Retour àux missions</span>
                    </a>
                </div>
            </header>

            <section class="admin-content">
                <div class="admin-title">
                    <i class="fa-solid fa-scroll"></i>
                    <h1>Modifier une mission</h1>
                </div>

                <section class="admin-form-card" id="add-mission" aria-labelledby="add-mission-title">
                    <div class="admin-form-head">
                        <div>
                            <h2 id="add-mission-title">Informations de la mission</h2>
                            <p>Modifie les informations de la mission active sur le cycle hebdomadaire. L'image peut venir de DofusDB, de ton PC ou d'un lien direct.</p>
                        </div>
                    </div>

                    <form class="admin-mission-form" id="mission-create-form" action="{{ route('admin.missions.update', $mission) }}" method="post" enctype="multipart/form-data" data-real-form data-admin-mission-form data-category-badges='@json($missionCategoryBadges)'>
                        @csrf
                        @method('patch')
                        <section class="admin-form-section">
                            <div class="admin-form-section-title">
                                <span>1</span>
                                <div>
                                    <h3>Informations</h3>
                                    <p>Commence par le titre et la catégorie.</p>
                                </div>
                            </div>

                            <div class="admin-form-grid">
                                <label class="admin-field admin-field--full" for="m-title">
                                    <span>Titre de la mission</span>
                                    <input id="m-title" name="title" type="text" value="{{ old('title', $mission->title) }}" placeholder="Ex: Plateau de Ush" required>
                                </label>

                                <label class="admin-field" for="m-category">
                                    <span>Catégorie</span>
                                    <select id="m-category" name="category" required data-mission-category>
                                        <option value="">Choisir une catégorie</option>
                                        <option value="donjon" @selected(old('category', $mission->category) === 'donjon')>Donjon</option>
                                        <option value="regulation" @selected(old('category', $mission->category) === 'regulation')>Régulation</option>
                                        <option value="expedition" @selected(old('category', $mission->category) === 'expedition')>Expédition</option>
                                        <option value="anomalie" @selected(old('category', $mission->category) === 'anomalie')>Anomalie</option>
                                        <option value="songe" @selected(old('category', $mission->category) === 'songe')>Songe</option>
                                    </select>
                                </label>

                                <label class="admin-field" for="m-dream-type" data-songe-field hidden>
                                    <span>Type de songe</span>
                                    <select id="m-dream-type" name="dream_type" data-songe-type disabled>
                                        <option value="">Choisir un type</option>
                                        @foreach (\App\Models\Mission::DREAM_TYPES as $value => $label)
                                            <option value="{{ $value }}" @selected(old('dream_type', $mission->dream_type) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="admin-field" for="m-anomaly-type" data-anomaly-field hidden>
                                    <span>Type d'anomalie</span>
                                    <select id="m-anomaly-type" name="anomaly_type" data-anomaly-type disabled>
                                        <option value="">Choisir un type</option>
                                        @foreach (\App\Models\Mission::ANOMALY_TYPES as $value => $label)
                                            <option value="{{ $value }}" @selected(old('anomaly_type', $mission->anomaly_type) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="admin-field" for="m-anomaly-level" data-anomaly-field hidden>
                                    <span>Niveau</span>
                                    <select id="m-anomaly-level" name="anomaly_level" data-anomaly-level disabled>
                                        <option value="">Choisir un niveau</option>
                                        @foreach (\App\Models\Mission::ANOMALY_LEVELS as $level)
                                            <option value="{{ $level }}" @selected((int) old('anomaly_level', $mission->anomaly_level) === $level)>Niveau {{ $level }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="admin-field" for="m-dream-floor" data-songe-field hidden>
                                    <span>Palier</span>
                                    <select id="m-dream-floor" name="dream_floor" data-songe-floor disabled>
                                        <option value="">Choisir un palier</option>
                                        @for ($floor = 1; $floor <= 5; $floor++)
                                            <option value="{{ $floor }}" @selected((int) old('dream_floor', $mission->dream_floor) === $floor)>Palier {{ $floor }}</option>
                                        @endfor
                                    </select>
                                </label>

                            </div>
                        </section>

                        <section class="admin-form-section admin-form-section--image" data-mission-image-section>
                            <div class="admin-form-section-title">
                                <span>2</span>
                                <div>
                                    <h3>Image</h3>
                                    <p>Choisis une catégorie avant de sélectionner une source d'image.</p>
                                </div>
                            </div>

                            <div class="admin-image-workspace">
                                <div class="admin-image-main">
                                    <div class="admin-image-mode" role="radiogroup" aria-label="Source de l'image">
                                        <label><input type="radio" name="image_mode" value="api" @checked($missionImageMode === 'api') data-image-mode disabled> Recherche DofusDB</label>
                                        <label><input type="radio" name="image_mode" value="upload" @checked($missionImageMode === 'upload') data-image-mode disabled> Depuis mon PC</label>
                                        <label><input type="radio" name="image_mode" value="url" @checked($missionImageMode === 'url') data-image-mode disabled> Lien image</label>
                                    </div>

                                    <div class="admin-image-source is-active" data-image-source="api">
                                        <label class="admin-field" for="m-monster-search">
                                            <span>Recherche du monstre</span>
                                            <input id="m-monster-search" name="monster_search" type="search" placeholder="Choisis d'abord une catégorie" autocomplete="off" data-monster-search disabled>
                                        </label>
                                        <div class="admin-monster-results" data-monster-results aria-live="polite">
                                            <p>Choisis une catégorie puis cherche un monstre.</p>
                                        </div>
                                    </div>

                                    <div class="admin-image-source" data-image-source="upload" data-media-picker-url="{{ route('admin.mediatheque.images', ['directory' => 'missions']) }}" hidden>
                                        <div class="admin-media-choice">
                                            <button class="admin-secondary-button" type="button" data-open-media-picker>
                                                <i class="fa-regular fa-image"></i>
                                                <span>Choisir depuis la médiathèque</span>
                                            </button>
                                        </div>
                                        <label class="admin-field admin-field--file-hidden" for="m-image-file">
                                            <span>Images depuis ton PC</span>
                                            <input id="m-image-file" name="image_files[]" type="file" accept="image/*" multiple data-image-file disabled>
                                        </label>
                                        <div class="admin-upload-preview-list" data-upload-preview-list aria-live="polite"></div>
                                    </div>

                                    <div class="admin-image-source" data-image-source="url" hidden>
                                        <label class="admin-field" for="m-image-url">
                                            <span>Lien de l'image</span>
                                            <input id="m-image-url" name="image_url" type="url" value="{{ old('image_url', $missionImageMode === 'url' ? $mission->image_path : '') }}" placeholder="https://..." data-image-url disabled>
                                        </label>
                                    </div>
                                </div>

                                <aside class="admin-image-preview" aria-label="Aperçu de l'image">
                                    <img src="{{ $mission->imageUrl() }}" alt="" data-image-preview>
                                    <button class="admin-image-preview__remove" type="button" data-remove-main-upload aria-label="Retirer l'image" hidden>
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </aside>
                            </div>

                            <input type="hidden" name="selected_image" value="{{ old('selected_image', $mission->image_path) }}" data-selected-image>
                            <input type="hidden" name="monster_id" value="{{ old('monster_id', $mission->monster_id) }}" data-selected-monster-id>
                        </section>

                        <div class="admin-form-actions">
                            <button class="admin-create-button" type="submit">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Enregistrer les modifications</span>
                            </button>
                        </div>
                    </form>
                </section>

            </section>
        </main>
@endsection
