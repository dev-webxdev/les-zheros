@extends('layouts.front')

@section('title', 'Guides | Les Zheros')
@section('description', 'Guides de mission de la guilde Les Zheros.')
@php($bodyClass = 'home-page guides-page')
@php($activePage = 'guides')

@section('content')
<section class="page-hero guide-hero">
    <div class="container page-hero__content">
        <span class="section-kicker">Bibliotheque tactique</span>
        <h1>Guides de <span>mission</span></h1>
        <p>Retrouve les fiches utiles pour les boss, placements et mecaniques qui demandent un rappel avant de partir.</p>
    </div>
</section>

<section class="guide-catalog" data-guide-catalog>
    <div class="container">
        <form class="guide-filters" aria-label="Filtres des guides">
            <label class="guide-filter-field" for="guide-filter-category">
                <span>Categorie</span>
                <select id="guide-filter-category" data-guide-filter="category">
                    <option value="">Toutes</option>
                    @foreach (array_intersect_key(\App\Models\Guide::CATEGORIES, array_flip(['donjon', 'expedition'])) as $category => $label)
                        <option value="{{ $category }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="guide-filter-field" for="guide-filter-search">
                <span>Recherche</span>
                <input id="guide-filter-search" type="search" placeholder="Ex: Nidas, Koutoulou, anomalie..." data-guide-filter="search">
            </label>

            <button class="btn btn--outline" type="reset" data-guide-reset>
                <i class="fa-solid fa-rotate-left"></i>
                <span>Réinitialiser</span>
            </button>
        </form>

        <div class="guide-catalog-head">
            <div>
                <span class="section-kicker">Guides disponibles</span>
                <h2>Fiches récentes</h2>
            </div>
            <p><strong data-guide-count>{{ $guides->count() }}</strong> guides affichés</p>
        </div>

        <div class="guide-grid" data-guide-grid>
            @foreach ($guides as $guide)
                <a class="guide-card guide-card--{{ $guide->category }}" href="{{ route('guides.show', $guide) }}" data-guide-card data-category="{{ $guide->category }}" data-search="{{ \Illuminate\Support\Str::lower($guide->title.' '.$guide->summary.' '.implode(' ', $guide->chips ?? [])) }}">
                    <div class="guide-card__scene">
                        <span class="guide-card__type">{{ $guide->categoryLabel() }}</span>
                        <img src="{{ $guide->coverUrl() }}" alt="{{ $guide->title }}" loading="lazy">
                    </div>
                    <div class="guide-card__body">
                        <h2>{{ $guide->title }}</h2>
                        <p>{{ $guide->summary }}</p>
                        <div class="guide-card__chips">
                            @foreach (array_slice($guide->chips ?? [], 0, 3) as $chip)
                                <span>{{ $chip }}</span>
                            @endforeach
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <p class="guide-empty" data-guide-empty data-empty-default="Aucun guide disponible pour le moment." data-empty-filtered="Aucun guide ne correspond aux filtres sélectionnés." @if($guides->isNotEmpty()) hidden @endif>Aucun guide disponible pour le moment.</p>
    </div>
</section>
@endsection
