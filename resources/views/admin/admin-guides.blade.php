@extends('layouts.admin')

@section('title', 'Guides | Les Zheros')
@section('description', 'Administration des guides de missions de la guilde Les Zheros.')
@php($activeAdmin = 'admin-guides')

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Guides</p>
        </div>

        <div class="admin-actions">
            <label class="admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Rechercher...">
            </label>
            <a class="admin-secondary-button" href="{{ route('admin.guides.trash') }}">
                <i class="fa-regular fa-trash-can"></i>
                <span>Corbeille</span>
            </a>
            <a class="admin-create-button" href="{{ route('admin.guides.create') }}">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Créer un guide</span>
            </a>
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title admin-title--split">
            <div>
                <i class="fa-solid fa-book-open"></i>
                <h1>Guides de missions</h1>
            </div>
            <p>Des fiches courtes pour aider la guilde sur les combats, prérequis et placements difficiles.</p>
        </div>

        <section class="admin-guide-layout" aria-label="Gestion des guides">
            <aside class="admin-guide-panel" aria-label="Résumé">
                <span class="admin-guide-panel__eyebrow">Bibliothèque</span>
                <strong>{{ $guideCount ?? $guides->total() }} guides actifs</strong>
                <p>Organise les guides par mission pour les retrouver rapidement au moment de valider une semaine compliquée.</p>
                <div class="admin-guide-stats">
                    @foreach (\App\Models\Guide::CATEGORIES as $category => $label)
                        <span><strong>{{ $categoryCounts[$category] ?? 0 }}</strong> {{ $label }}</span>
                    @endforeach
                </div>
            </aside>

            <div class="admin-guide-list">
                @forelse ($guides as $guide)
                    <article class="admin-guide-card">
                        <div class="admin-guide-card__media">
                            <img src="{{ $guide->coverUrl() }}" alt="{{ $guide->title }}">
                        </div>
                        <div class="admin-guide-card__body">
                            <div class="admin-guide-card__meta">
                                <span class="admin-tag">{{ $guide->categoryLabel() }}</span>
                                <span @class(['admin-tag', 'admin-tag--success' => $guide->is_published])>{{ $guide->is_published ? 'Publié' : 'Brouillon' }}</span>
                                <span>Mis à jour le {{ $guide->updated_at?->translatedFormat('d M Y') }}</span>
                            </div>
                            <h2>{{ $guide->title }}</h2>
                            <p>{{ $guide->summary }}</p>
                            <div class="admin-guide-chapters">
                                @foreach (array_slice($guide->sections ?? [], 0, 4) as $section)
                                    <span>{{ $section['title'] ?? 'Section' }}</span>
                                @endforeach
                                @if(empty($guide->sections))
                                    <span>Aucune section</span>
                                @endif
                            </div>
                        </div>
                        <div class="admin-guide-card__actions">
                            <a class="admin-secondary-button" href="{{ route('admin.guides.edit', $guide) }}"><i class="fa-regular fa-pen-to-square"></i><span>Modifier</span></a>
                            <form action="{{ route('admin.guides.destroy', $guide) }}" method="post" data-real-form>
                                @csrf
                                @method('delete')
                                <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Mettre {{ $guide->title }} à la corbeille" title="Corbeille"><i class="fa-regular fa-trash-can"></i></button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="admin-empty-state--panel">
                        <i class="fa-solid fa-book-open"></i>
                        <strong>Aucun guide</strong>
                        <span>Crée ton premier guide pour l'afficher sur le front.</span>
                    </div>
                @endforelse
            </div>
        </section>
        @include('partials.admin-pagination', ['paginator' => $guides])
    </section>
</main>
@endsection
