@extends('layouts.admin')

@section('title', 'Dashboard | Les Zheros')
@section('description', 'Vue d\'ensemble de l\'administration de la guilde Les Zheros.')
@php($activeAdmin = 'admin')
@php($adminUser = auth()->user())
@php($can = fn (string $area): bool => (bool) $adminUser?->canAccessAdminArea($area))
@push('scripts')
<script src="{{ asset('assets/js/admin-dashboard.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Dashboard</p>
        </div>

        <div class="admin-actions">
            <button class="admin-secondary-button" type="button" data-dashboard-edit>
                <i class="fa-solid fa-table-cells-large"></i>
                <span>Modifier la disposition</span>
            </button>
        </div>
    </header>

    <section class="admin-content admin-dashboard">
        <div class="admin-title admin-title--split">
            <div>
                <i class="fa-solid fa-house"></i>
                <h1>Dashboard</h1>
            </div>
            <p>Ton espace de travail admin, sans doubler les pages de gestion.</p>
        </div>

        <section class="admin-dashboard-layout" data-dashboard-layout>
            <div class="admin-dashboard-column" data-dashboard-column="left" aria-label="Colonne gauche">
                <article class="admin-dashboard-panel" draggable="true" data-dashboard-widget="notes">
                    <div class="admin-dashboard-panel__head">
                        <div>
                            <h2>Notes admin</h2>
                            <p>Un bloc simple pour les rappels d'equipe.</p>
                        </div>
                    </div>
                    <div class="admin-dashboard-widget-tools" aria-label="Disposition du widget">
                        <button type="button" data-dashboard-move="toggle" title="Changer de colonne"><i class="fa-solid fa-right-left"></i></button>
                    </div>
                    <textarea class="admin-dashboard-note" rows="7" placeholder="Ex: penser a publier la galerie apres la sortie de vendredi."></textarea>
                    <div class="admin-dashboard-note-actions">
                        <button class="admin-create-button" type="button" data-dashboard-note-save>
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Enregistrer</span>
                        </button>
                    </div>
                </article>
            </div>

            <div class="admin-dashboard-column" data-dashboard-column="right" aria-label="Colonne droite">
                <article class="admin-dashboard-panel" draggable="true" data-dashboard-widget="shortcuts">
                    <div class="admin-dashboard-panel__head">
                        <div>
                            <h2>Raccourcis</h2>
                            <p>Les creations qu'on ouvre souvent.</p>
                        </div>
                    </div>
                    <div class="admin-dashboard-widget-tools" aria-label="Disposition du widget">
                        <button type="button" data-dashboard-move="toggle" title="Changer de colonne"><i class="fa-solid fa-right-left"></i></button>
                    </div>
                    <div class="admin-dashboard-shortcuts">
                        @if ($can('announcements'))
                            <a href="{{ route('admin.annonces.create') }}"><i class="fa-solid fa-bullhorn"></i><span>Annonce</span></a>
                        @endif
                        @if ($can('missions'))
                            <a href="{{ route('admin.missions.create') }}"><i class="fa-solid fa-scroll"></i><span>Mission</span></a>
                        @endif
                        @if ($can('guides'))
                            <a href="{{ route('admin.guides.create') }}"><i class="fa-solid fa-book-open"></i><span>Guide</span></a>
                        @endif
                        @if ($can('gallery'))
                            <a href="{{ route('admin.galerie.create') }}"><i class="fa-regular fa-images"></i><span>Image galerie</span></a>
                        @endif
                        @if ($can('lottery'))
                            <a href="{{ route('admin.loterie.index') }}"><i class="fa-solid fa-dice"></i><span>Loterie</span></a>
                        @endif
                        @if ($can('users'))
                            <a href="{{ route('admin.utilisateurs.create') }}"><i class="fa-solid fa-user-plus"></i><span>Utilisateur</span></a>
                        @endif
                    </div>
                </article>
            </div>
        </section>
    </section>
</main>

@endsection
