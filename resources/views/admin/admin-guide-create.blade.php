@extends('layouts.admin')

@section('title', ($guide->exists ? 'Modifier' : 'Créer').' un guide | Les Zheros')
@section('description', 'Création et modification d\'un guide de mission pour la guilde Les Zheros.')
@php
    $activeAdmin = 'admin-guides';
@endphp
@push('scripts')
<script src="{{ asset('assets/js/admin-guides.js') }}?v={{ filemtime(public_path('assets/js/admin-guides.js')) }}" defer></script>
@endpush
@push('scripts')
<script src="{{ asset('assets/js/admin-rich-editor.js') }}?v={{ filemtime(public_path('assets/js/admin-rich-editor.js')) }}" defer></script>
@endpush

@section('admin')
@php
    $coverPath = old('cover_path', $guide->cover_path);
    $guideSections = collect(old('sections', $guide->sections ?? []))
        ->map(function ($section) {
            $section = is_array($section) ? $section : [];
            $images = collect($section['images'] ?? [])
                ->when(! empty($section['image']), fn ($items) => $items->prepend([
                    'image' => $section['image'],
                    'caption' => $section['caption'] ?? '',
                ]))
                ->filter(fn ($image) => is_array($image) && ! empty($image['image']))
                ->values()
                ->all();

            return [
                ...$section,
                'kind' => $section['kind'] ?? 'strategy',
                'images' => $images,
            ];
        });
    $placementSection = $guideSections->firstWhere('kind', 'placement') ?? ['kind' => 'placement', 'title' => 'Placement', 'body' => '', 'images' => []];
    $strategySections = $guideSections->filter(fn ($section) => ($section['kind'] ?? 'strategy') === 'strategy')->values();
    $spellSections = $guideSections->filter(fn ($section) => ($section['kind'] ?? '') === 'spells')->values();
    $sectionFormIndex = 0;
    $autosaveDraft = ! $guide->exists || ! $guide->is_published;
@endphp
<main class="admin-main">
            <header class="admin-topbar">
                <div class="admin-breadcrumb">
                    <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                        <i class="fa-solid fa-table-columns"></i>
                    </button>
                    <span></span>
                    <p>Guides / {{ $guide->exists ? 'Modifier' : 'Créer' }}</p>
                </div>

                <div class="admin-actions">
                    <a class="admin-secondary-button" href="{{ route('admin.guides.index') }}">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Retour aux guides</span>
                    </a>
                </div>
            </header>

            <section class="admin-content admin-content--editor">
                <form id="guide-form" class="admin-guide-editor admin-guide-editor--cms" action="{{ $guide->exists ? route('admin.guides.update', $guide) : route('admin.guides.store') }}" method="post" enctype="multipart/form-data" data-real-form @if($autosaveDraft) data-guide-autosave="{{ route('admin.guides.autosave') }}" @endif>
                    @csrf
                    @if($guide->exists)
                        @method('patch')
                    @endif
                    <input type="hidden" name="auto_draft_id" value="{{ old('auto_draft_id', $autosaveDraft && $guide->exists ? $guide->id : null) }}" data-guide-auto-draft-id>
                    <input type="hidden" name="cover_path" value="{{ $coverPath }}" data-guide-cover-path-input>
                    <section class="admin-guide-compose">
                        <nav class="admin-guide-editor-tabs" aria-label="Sections du guide" data-guide-editor-tabs>
                            <button class="is-active" type="button" data-guide-editor-tab="infos" aria-pressed="true"><i class="fa-solid fa-circle-info"></i><span>Infos</span></button>
                            <button type="button" data-guide-editor-tab="resume" aria-pressed="false"><i class="fa-solid fa-list-check"></i><span>Résumé</span></button>
                            <button type="button" data-guide-editor-tab="placement" aria-pressed="false"><i class="fa-solid fa-map-location-dot"></i><span>Placement</span></button>
                            <button type="button" data-guide-editor-tab="strategy" aria-pressed="false"><i class="fa-solid fa-route"></i><span>Stratégie</span></button>
                            <button type="button" data-guide-editor-tab="spells" aria-pressed="false"><i class="fa-solid fa-wand-sparkles"></i><span>Sorts</span></button>
                        </nav>

                        <section class="admin-guide-builder-card is-active" data-guide-editor-panel="infos">
                            <div class="admin-guide-builder-card__head">
                                <span>Informations</span>
                                <p>Ce bloc nourrit le haut de la page guide.</p>
                            </div>
                            <div class="admin-guide-builder-grid">
                                <label class="admin-guide-title-field" for="guide-title">
                                    <span>Titre du guide</span>
                                    <input id="guide-title" name="title" type="text" value="{{ old('title', $guide->title) }}" placeholder="Ex: Belvédère d'Ilyzaelle" required data-guide-title-input>
                                </label>

                                <label class="admin-field" for="guide-type">
                                    <span>Catégorie</span>
                                    <select id="guide-type" name="category" required data-guide-category-select>
                                        @foreach (\App\Models\Guide::CATEGORIES as $category => $label)
                                            <option value="{{ $category }}" @selected(old('category', $guide->category) === $category)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="admin-guide-summary admin-guide-builder-grid__full" for="guide-summary">
                                    <span>Résumé court</span>
                                    <textarea id="guide-summary" name="summary" rows="2" placeholder="Ex: Placement conseillé, lecture de map et rappels de focus." required>{{ old('summary', $guide->summary) }}</textarea>
                                </label>

                                <label class="admin-field admin-guide-builder-grid__full" for="guide-chips">
                                    <span class="admin-label-with-help">Tags du haut <span class="admin-help-tip" tabindex="0"><i class="fa-solid fa-circle-info"></i><span class="admin-help-popover"><strong>Tags du haut</strong><span class="admin-help-screenshot admin-help-screenshot--tags"><span><em>Placement</em><em>Boss</em><em>Technique</em></span></span><small>Sépare par des virgules. Ces tags apparaissent sous le résumé du guide.</small></span></span></span>
                                    <input id="guide-chips" name="chips" type="text" value="{{ old('chips', implode(', ', $guide->chips ?? [])) }}" placeholder="Ex: Placement, Boss, Technique" data-guide-chips-input>
                                </label>
                            </div>
                        </section>

                        <section class="admin-guide-builder-card" data-guide-editor-panel="resume" hidden>
                            <div class="admin-guide-builder-card__head">
                                <span class="admin-label-with-help">Résumé <span class="admin-help-tip" tabindex="0"><i class="fa-solid fa-circle-info"></i><span class="admin-help-popover"><strong>Résumé</strong><span class="admin-help-screenshot admin-help-screenshot--checklist"><em>Prévoir une équipe capable de replacer rapidement.</em><em>Garder les personnages fragiles hors des lignes dangereuses.</em><em>Annoncer le focus avant le début du tour critique.</em></span><small>Checklist affichée dans l'onglet Résumé de la page guide.</small></span></span></span>
                                <button class="admin-secondary-button admin-guide-builder-add" type="button" data-guide-add-check>
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Ajouter un point</span>
                                </button>
                            </div>
                            <div class="admin-guide-checklist-fields" data-guide-checklist-list>
                                <p class="admin-guide-empty-note" data-guide-empty-checklist @if(count($guide->checklist ?? []) > 0) hidden @endif>Aucun point pour le moment.</p>
                                @foreach ($guide->checklist ?? [] as $pointIndex => $point)
                                    <div class="admin-guide-check-row" data-guide-check-row>
                                        <span class="admin-guide-drag-handle" data-guide-drag-handle aria-label="Déplacer ce point" title="Déplacer"><i class="fa-solid fa-grip-vertical"></i></span>
                                        <label class="admin-field" for="guide-check-existing-{{ $pointIndex }}">
                                            <span data-guide-check-label>Point {{ $pointIndex + 1 }}</span>
                                            <input id="guide-check-existing-{{ $pointIndex }}" name="checklist[]" type="text" value="{{ $point }}" placeholder="Ex: Garder les personnages fragiles hors des lignes dangereuses.">
                                        </label>
                                        <button class="admin-guide-remove-button" type="button" data-guide-remove-check aria-label="Supprimer ce point" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <section class="admin-guide-builder-card admin-guide-builder-card--split" data-guide-editor-panel="placement" hidden>
                            <div class="admin-guide-builder-card__head">
                                <span>Placement</span>
                                <p>Texte, map et images affichés dans l'onglet Placement.</p>
                            </div>
                            @php
                                $placementIndex = $sectionFormIndex++;
                            @endphp
                            <article class="admin-guide-section-card admin-guide-section-card--flat" data-guide-section-card>
                                <input type="hidden" name="sections[{{ $placementIndex }}][kind]" value="placement">
                                <input type="hidden" name="sections[{{ $placementIndex }}][title]" value="Placement">
                                <label class="admin-field" for="guide-placement-body">
                                    <span>Texte de placement</span>
                                    @include('admin.partials.rich-editor', [
                                        'id' => 'guide-placement-body',
                                        'name' => "sections[$placementIndex][body]",
                                        'value' => $placementSection['body'] ?? '',
                                        'placeholder' => 'Ajoute les consignes de placement, les variantes ou les remarques importantes.',
                                        'surfaceClass' => 'admin-rich-editor__surface--guide',
                                    ])
                                </label>
                                <div class="admin-guide-section-media-slot" data-guide-section-media-slot>
                                    @foreach (($placementSection['images'] ?? []) as $imageIndex => $image)
                                        <div class="admin-guide-section-media-item" data-guide-section-media-item>
                                            <label class="admin-guide-section-image" for="guide-placement-image-{{ $imageIndex }}">
                                                <img class="admin-cover-preview admin-cover-preview--strategy" src="{{ $image['image'] }}" alt="" data-guide-section-image-preview>
                                                <input id="guide-placement-image-{{ $imageIndex }}" class="admin-cover-input" name="sections[{{ $placementIndex }}][images][{{ $imageIndex }}][image]" type="file" accept="image/*" data-guide-section-image-input>
                                            </label>
                                            <label class="admin-field" for="guide-placement-caption-{{ $imageIndex }}">
                                                <span>Texte sous l'image</span>
                                                <textarea id="guide-placement-caption-{{ $imageIndex }}" name="sections[{{ $placementIndex }}][images][{{ $imageIndex }}][caption]" rows="2" data-autogrow>{{ $image['caption'] ?? '' }}</textarea>
                                            </label>
                                            <button class="admin-guide-remove-button" type="button" data-guide-remove-section-image aria-label="Supprimer cette image" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                        </div>
                                    @endforeach
                                    <button class="admin-secondary-button admin-guide-builder-add" type="button" data-guide-add-section-image><i class="fa-regular fa-image"></i><span>Ajouter une image</span></button>
                                </div>
                            </article>
                            <div class="admin-guide-map-field">
                                <label class="admin-cover-picker admin-cover-picker--wide" for="guide-map">
                                    <span class="admin-guide-upload-placeholder" data-guide-map-empty @if($guide->map_path) hidden @endif><i class="fa-regular fa-image"></i> Ajouter une map</span>
                                    <img class="admin-cover-preview admin-cover-preview--map" src="{{ $guide->map_path }}" alt="" data-guide-map-preview @if(!$guide->map_path) hidden @endif>
                                    <span>Aperçu de la map</span>
                                </label>
                                <input id="guide-map" class="admin-cover-input" name="map" type="file" accept="image/*" data-guide-map-input>
                            </div>
                        </section>

                        <section class="admin-guide-builder-card" data-guide-editor-panel="strategy" hidden>
                            <div class="admin-guide-builder-card__head">
                                <span>Stratégie</span>
                                <button class="admin-secondary-button admin-guide-builder-add" type="button" data-guide-add-section="strategy">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Ajouter une section</span>
                                </button>
                            </div>
                            <div class="admin-guide-section-list" data-guide-section-list="strategy">
                                <p class="admin-guide-empty-note" data-guide-empty-sections @if($strategySections->isNotEmpty()) hidden @endif>Aucune section pour le moment.</p>
                                @foreach ($strategySections as $section)
                                    @php
                                        $sectionIndex = $sectionFormIndex++;
                                    @endphp
                                    <article class="admin-guide-section-card" data-guide-section-card>
                                        <input type="hidden" name="sections[{{ $sectionIndex }}][kind]" value="strategy">
                                        <div class="admin-guide-section-card__top">
                                            <span class="admin-guide-drag-handle" data-guide-drag-handle aria-label="Déplacer cette section" title="Déplacer"><i class="fa-solid fa-grip-vertical"></i></span>
                                            <label class="admin-field" for="guide-section-title-existing-{{ $sectionIndex }}">
                                                <span>Titre de section</span>
                                                <input id="guide-section-title-existing-{{ $sectionIndex }}" name="sections[{{ $sectionIndex }}][title]" type="text" value="{{ $section['title'] ?? '' }}" placeholder="Ex: Lecture de la map">
                                            </label>
                                            <button class="admin-guide-remove-button" type="button" data-guide-remove-section aria-label="Supprimer cette section" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                        </div>
                                        <label class="admin-field" for="guide-section-body-existing-{{ $sectionIndex }}">
                                            <span>Contenu</span>
                                            @include('admin.partials.rich-editor', [
                                                'id' => "guide-section-body-existing-$sectionIndex",
                                                'name' => "sections[$sectionIndex][body]",
                                                'value' => $section['body'] ?? '',
                                                'placeholder' => 'Explique le point important, le placement ou la mécanique.',
                                                'surfaceClass' => 'admin-rich-editor__surface--guide',
                                            ])
                                        </label>
                                        <div class="admin-guide-section-media-slot" data-guide-section-media-slot>
                                            @foreach (($section['images'] ?? []) as $imageIndex => $image)
                                                <div class="admin-guide-section-media-item" data-guide-section-media-item>
                                                    <label class="admin-guide-section-image" for="guide-section-image-existing-{{ $sectionIndex }}-{{ $imageIndex }}">
                                                        <img class="admin-cover-preview admin-cover-preview--strategy" src="{{ $image['image'] }}" alt="" data-guide-section-image-preview>
                                                        <input id="guide-section-image-existing-{{ $sectionIndex }}-{{ $imageIndex }}" class="admin-cover-input" name="sections[{{ $sectionIndex }}][images][{{ $imageIndex }}][image]" type="file" accept="image/*" data-guide-section-image-input>
                                                    </label>
                                                    <label class="admin-field" for="guide-section-caption-existing-{{ $sectionIndex }}-{{ $imageIndex }}">
                                                        <span>Texte sous l'image</span>
                                                        <textarea id="guide-section-caption-existing-{{ $sectionIndex }}-{{ $imageIndex }}" name="sections[{{ $sectionIndex }}][images][{{ $imageIndex }}][caption]" rows="2" data-autogrow>{{ $image['caption'] ?? '' }}</textarea>
                                                    </label>
                                                    <button class="admin-guide-remove-button" type="button" data-guide-remove-section-image aria-label="Supprimer cette image" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                                </div>
                                            @endforeach
                                            <button class="admin-secondary-button admin-guide-builder-add" type="button" data-guide-add-section-image><i class="fa-regular fa-image"></i><span>Ajouter une image</span></button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>

                        <section class="admin-guide-builder-card" data-guide-editor-panel="spells" hidden>
                            <div class="admin-guide-builder-card__head">
                                <span>Sorts des monstres</span>
                                <button class="admin-secondary-button admin-guide-builder-add" type="button" data-guide-add-section="spells">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>Ajouter un sort</span>
                                </button>
                            </div>
                            <div class="admin-guide-section-list" data-guide-section-list="spells">
                                <p class="admin-guide-empty-note" data-guide-empty-sections @if($spellSections->isNotEmpty()) hidden @endif>Aucun sort pour le moment.</p>
                                @foreach ($spellSections as $section)
                                    @php
                                        $sectionIndex = $sectionFormIndex++;
                                    @endphp
                                    <article class="admin-guide-section-card" data-guide-section-card>
                                        <input type="hidden" name="sections[{{ $sectionIndex }}][kind]" value="spells">
                                        <div class="admin-guide-section-card__top">
                                            <span class="admin-guide-drag-handle" data-guide-drag-handle aria-label="Déplacer ce sort" title="Déplacer"><i class="fa-solid fa-grip-vertical"></i></span>
                                            <label class="admin-field" for="guide-spell-title-existing-{{ $sectionIndex }}">
                                                <span>Nom du sort</span>
                                                <input id="guide-spell-title-existing-{{ $sectionIndex }}" name="sections[{{ $sectionIndex }}][title]" type="text" value="{{ $section['title'] ?? '' }}" placeholder="Ex: Attirance explosive">
                                            </label>
                                            <button class="admin-guide-remove-button" type="button" data-guide-remove-section aria-label="Supprimer ce sort" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                        </div>
                                        <label class="admin-field" for="guide-spell-body-existing-{{ $sectionIndex }}">
                                            <span>Effet du sort</span>
                                            @include('admin.partials.rich-editor', [
                                                'id' => "guide-spell-body-existing-$sectionIndex",
                                                'name' => "sections[$sectionIndex][body]",
                                                'value' => $section['body'] ?? '',
                                                'placeholder' => 'Décris la portée, la zone, le danger et comment l’éviter.',
                                                'surfaceClass' => 'admin-rich-editor__surface--guide',
                                            ])
                                        </label>
                                        <div class="admin-guide-section-media-slot" data-guide-section-media-slot>
                                            @foreach (($section['images'] ?? []) as $imageIndex => $image)
                                                <div class="admin-guide-section-media-item" data-guide-section-media-item>
                                                    <label class="admin-guide-section-image" for="guide-spell-image-existing-{{ $sectionIndex }}-{{ $imageIndex }}">
                                                        <img class="admin-cover-preview admin-cover-preview--strategy" src="{{ $image['image'] }}" alt="" data-guide-section-image-preview>
                                                        <input id="guide-spell-image-existing-{{ $sectionIndex }}-{{ $imageIndex }}" class="admin-cover-input" name="sections[{{ $sectionIndex }}][images][{{ $imageIndex }}][image]" type="file" accept="image/*" data-guide-section-image-input>
                                                    </label>
                                                    <label class="admin-field" for="guide-spell-caption-existing-{{ $sectionIndex }}-{{ $imageIndex }}">
                                                        <span>Texte sous l'image</span>
                                                        <textarea id="guide-spell-caption-existing-{{ $sectionIndex }}-{{ $imageIndex }}" name="sections[{{ $sectionIndex }}][images][{{ $imageIndex }}][caption]" rows="2" data-autogrow>{{ $image['caption'] ?? '' }}</textarea>
                                                    </label>
                                                    <button class="admin-guide-remove-button" type="button" data-guide-remove-section-image aria-label="Supprimer cette image" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                                </div>
                                            @endforeach
                                            <button class="admin-secondary-button admin-guide-builder-add" type="button" data-guide-add-section-image><i class="fa-regular fa-image"></i><span>Ajouter une image</span></button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    </section>

                    <aside class="admin-guide-editor__aside admin-guide-editor__aside--cms">
                        <section class="admin-guide-side-card">
                            <div class="admin-guide-switch-row">
                                <span>Publié</span>
                                <label class="admin-switch">
                                    <input type="checkbox" name="published" @checked(old('published', $guide->is_published ?? true))>
                                    <span></span>
                                </label>
                            </div>
                        </section>

                        <section class="admin-guide-side-card admin-guide-side-card--cover">
                            <label class="admin-cover-picker" for="guide-cover">
                                <img class="admin-cover-preview admin-cover-preview--contain" src="{{ $coverPath ?: $guide->coverUrl() }}" alt="" data-guide-cover-preview>
                                <span>Aperçu</span>
                            </label>
                            <input id="guide-cover" class="admin-cover-input" name="cover" type="file" accept="image/*" data-guide-cover-input>
                        </section>

                        <section class="admin-guide-side-card">
                            <label class="admin-field" for="guide-mission">
                                <span>Mission liée</span>
                                <select id="guide-mission" name="mission_id">
                                    <option value="">Aucune mission liée</option>
                                    @foreach ($missions as $mission)
                                        <option value="{{ $mission->id }}" data-mission-title="{{ $mission->title }}" data-mission-category="{{ $mission->category }}" data-mission-image="{{ $mission->imageUrl() }}" @selected((int) old('mission_id', $guide->mission_id) === $mission->id)>{{ $mission->title }}</option>
                                    @endforeach
                                </select>
                            </label>

                        </section>

                        <section class="admin-guide-side-card">
                            <label class="admin-field" for="guide-date">
                                <span>Publication</span>
                                <input id="guide-date" name="published_at" type="datetime-local" value="{{ old('published_at', $guide->published_at?->format('Y-m-d\TH:i')) }}">
                            </label>
                        </section>

                        <button class="admin-create-button admin-guide-save-button" type="submit">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Enregistrer</span>
                        </button>
                        @if($autosaveDraft)
                            <p class="admin-guide-autosave-status" data-guide-autosave-status>Sauvegarde auto prête</p>
                        @endif
                    </aside>
                </form>
            </section>
        </main>
@endsection


