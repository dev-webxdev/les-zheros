@extends('layouts.admin')

@section('title', ($outing->exists ? 'Modifier' : 'Créer').' une sortie | Les Zheros')
@section('description', 'Création et modification d’une sortie de mission de guilde Les Zheros.')
@php($activeAdmin = 'admin-sorties')
@push('scripts')
<script src="{{ asset('assets/js/admin-sorties.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Sorties / {{ $outing->exists ? 'Modifier' : 'Créer' }}</p>
        </div>

        <div class="admin-actions">
            <a class="admin-secondary-button" href="{{ route('admin.sorties.index') }}">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Retour aux sorties</span>
            </a>
        </div>
    </header>

    <section class="admin-content admin-outings admin-outings--create" data-admin-outings data-outing-initial-schedule='@json(old('schedule') ? json_decode(old('schedule'), true) : ($outing->schedule ?? []))'>
        <div class="admin-title admin-title--split">
            <div>
                <i class="fa-solid fa-users"></i>
                <h1>{{ $outing->exists ? 'Modifier une sortie' : 'Créer une sortie' }}</h1>
            </div>
            <p>Renseigne l’essentiel, ajoute les jours et garde l’aperçu public sous les yeux.</p>
        </div>

        <div class="admin-outing-layout">
            <form id="outing-form" class="admin-outing-panel admin-outing-builder" action="{{ $outing->exists ? route('admin.sorties.update', $outing) : route('admin.sorties.store') }}" method="post" data-real-form data-outing-form>
                @csrf
                @if($outing->exists)
                    @method('patch')
                @endif
                <input type="hidden" name="schedule" value="{{ old('schedule', json_encode($outing->schedule ?? [])) }}" data-outing-schedule-input>

                <div class="admin-outing-panel__head">
                    <div>
                        <h2>Vote de sortie</h2>
                        <p>Un formulaire court pour éviter de ressaisir chaque créneau à la main.</p>
                    </div>
                    <span class="admin-tag admin-tag--primary" data-outing-slot-count>0 créneau</span>
                </div>

                <section class="admin-outing-section">
                    <div class="admin-outing-section__title">
                        <span>1</span>
                        <div>
                            <h3>Informations</h3>
                            <p>Ce que les membres voient en premier.</p>
                        </div>
                    </div>

                    <div class="admin-outing-section__body">
                        <div class="admin-outing-grid">
                            <label class="admin-field admin-field--full" for="outing-title">
                                <span>Titre</span>
                                <input id="outing-title" name="title" type="text" value="{{ old('title', $outing->title) }}" placeholder="Ex: Songes du week-end" data-outing-title required>
                            </label>
                            <label class="admin-field admin-field--full" for="outing-description">
                                <span>Description courte</span>
                                <textarea id="outing-description" name="description" rows="4" data-outing-description>{{ old('description', $outing->description) }}</textarea>
                            </label>
                            <label class="admin-field" for="outing-places">
                                <span>Places max</span>
                                <input id="outing-places" name="places" type="number" min="1" max="16" value="{{ old('places', $outing->places ?? 8) }}" data-outing-places required>
                            </label>
                            <label class="admin-field" for="outing-close">
                                <span>Clôture des votes</span>
                                <input id="outing-close" name="close_at" type="text" value="{{ old('close_at', $outing->close_at?->format('d/m/Y H:i')) }}" placeholder="19/05/2026 13:00" inputmode="numeric" autocomplete="off" data-outing-close>
                            </label>
                        </div>
                    </div>
                </section>

                <section class="admin-outing-section">
                    <div class="admin-outing-section__title">
                        <span>2</span>
                        <div>
                            <h3>Créneaux</h3>
                            <p>Ajoute un jour puis plusieurs heures séparées par des virgules.</p>
                        </div>
                    </div>

                    <div class="admin-outing-section__body">
                        <div class="admin-outing-slot-actions" aria-label="Actions des créneaux">
                            <button class="admin-secondary-button" type="button" data-outing-clear>
                                <i class="fa-solid fa-eraser"></i>
                                <span>Vider les créneaux</span>
                            </button>
                        </div>

                        <div class="admin-outing-quick-add">
                            <label class="admin-field" for="outing-slot-date">
                                <span>Date</span>
                                <input id="outing-slot-date" type="date" data-outing-new-date>
                            </label>
                            <label class="admin-field" for="outing-slot-times">
                                <span>Heures</span>
                                <input id="outing-slot-times" type="text" placeholder="15:00, 19:00" data-outing-new-times>
                            </label>
                            <button class="admin-secondary-button" type="button" data-outing-add-day>
                                <i class="fa-solid fa-plus"></i>
                                <span>Ajouter</span>
                            </button>
                        </div>

                        <div class="admin-outing-days" data-outing-days aria-live="polite"></div>
                    </div>
                </section>

                <div class="admin-form-actions admin-outing-submit">
                    <button class="admin-create-button" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Enregistrer</span>
                    </button>
                </div>
            </form>

            <aside class="admin-outing-aside">
                @if($outing->exists)
                    <section class="admin-guide-side-card">
                        <div class="admin-guide-switch-row">
                            <span>Publié</span>
                            <label class="admin-switch">
                                <input type="checkbox" name="published" form="outing-form" @checked(old('published', $outing->is_published ?? true))>
                                <span></span>
                            </label>
                        </div>
                    </section>
                @endif

                <section class="admin-outing-preview admin-outing-panel" aria-label="Aperçu de la sortie">
                    <div class="admin-outing-panel__head">
                        <div>
                            <h2>Aperçu public</h2>
                            <p>Ce que la guilde verra sur la page Sorties.</p>
                        </div>
                        <span class="admin-tag admin-tag--success">Ouvert</span>
                    </div>

                    <article class="admin-outing-public-card">
                        <div class="admin-outing-public-card__head">
                            <div>
                                <h3 data-outing-preview-title>Nouvelle sortie</h3>
                                <p data-outing-preview-description>Ajoute une description courte pour donner le contexte.</p>
                            </div>
                            <div class="admin-outing-public-meta">
                                <span>Places max <strong data-outing-preview-places>8 joueurs</strong></span>
                                <span>Clôture <strong data-outing-preview-close>Non définie</strong></span>
                            </div>
                        </div>

                        <div class="admin-outing-preview-days" data-outing-preview-days></div>
                        <div class="admin-outing-preview-slots" data-outing-preview-slots></div>
                    </article>
                </section>
            </aside>
        </div>
    </section>
</main>
@endsection
