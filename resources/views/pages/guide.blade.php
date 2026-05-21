@extends('layouts.front')

@section('title', ($guide->title ?? 'Guide').' | Les Zheros')
@section('description', $guide->summary ?? 'Guide de mission de la guilde Les Zheros.')
@php
    $bodyClass = 'home-page guide-detail-page';
    $activePage = 'guide';
@endphp

@section('content')
<script id="guide-detail-data" type="application/json">@json($guide->frontPayload())</script>

<section class="guide-detail" data-guide-detail>
    <div class="container">
        <a class="guide-detail__back" href="{{ route('guides.index') }}"><i class="fa-solid fa-arrow-left"></i><span>Retour aux guides</span></a>

        <div class="guide-detail-layout">
            <article class="guide-detail-main">
                <header class="guide-detail-hero">
                    <div class="guide-detail-hero__copy">
                        <span class="guide-detail__type" data-guide-detail-type>{{ $guide->categoryLabel() }}</span>
                        <h1 data-guide-detail-title>{{ $guide->title }}</h1>
                        <p data-guide-detail-summary>{{ $guide->summary }}</p>
                        <div class="guide-detail__chips" data-guide-detail-chips></div>
                    </div>
                    <div class="guide-detail-hero__media">
                        <img src="{{ $guide->coverUrl() }}" alt="{{ $guide->title }}" data-guide-detail-image>
                    </div>
                </header>

                <nav class="guide-detail-tabs" aria-label="Sections du guide" role="tablist">
                    <a class="is-active" href="#resume" role="tab" aria-selected="true" data-guide-tab="resume"><i class="fa-regular fa-rectangle-list"></i><span>Résumé</span></a>
                    <a href="#placement" role="tab" aria-selected="false" data-guide-tab="placement"><i class="fa-solid fa-map-location-dot"></i><span>Placement</span></a>
                    <a href="#strategie" role="tab" aria-selected="false" data-guide-tab="strategie"><i class="fa-solid fa-list-check"></i><span>Stratégie</span></a>
                    <a href="#sorts" role="tab" aria-selected="false" data-guide-tab="sorts"><i class="fa-solid fa-wand-sparkles"></i><span>Sorts</span></a>
                </nav>

                <section class="guide-detail-section guide-detail-panel is-active" id="resume" role="tabpanel" data-guide-panel="resume">
                    <span class="guide-detail-card__label">Avant le départ</span>
                    <ul class="guide-detail-checklist" data-guide-detail-checklist></ul>
                </section>

                <section class="guide-detail-section guide-detail-panel" id="placement" role="tabpanel" data-guide-panel="placement" hidden>
                    <div class="guide-detail-map @if(!$guide->mapUrl()) is-empty @endif">
                        <img src="{{ $guide->mapUrl() }}" alt="Illustration de placement" data-guide-detail-map @if(!$guide->mapUrl()) hidden @endif>
                        <p class="guide-detail-empty" data-guide-detail-map-empty hidden>Aucune image de placement mise pour le moment.</p>
                    </div>
                    <div class="guide-detail-steps" data-guide-detail-placement-content></div>
                </section>

                <section class="guide-detail-section guide-detail-section--article guide-detail-panel" id="strategie" role="tabpanel" data-guide-panel="strategie" hidden>
                    <div class="guide-detail-steps" data-guide-detail-content></div>
                </section>
                <section class="guide-detail-section guide-detail-section--article guide-detail-panel" id="sorts" role="tabpanel" data-guide-panel="sorts" hidden>
                    <div class="guide-detail-steps" data-guide-detail-spells></div>
                </section>
            </article>

            <aside class="guide-detail-sidebar" aria-label="Sommaire de la stratégie">
                <span class="guide-detail-sidebar__label">Stratégie</span>
                <nav data-guide-detail-nav></nav>
            </aside>
        </div>
    </div>
</section>
@endsection

