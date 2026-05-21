@extends('layouts.front')

@section('title', 'Les Zheros | Guilde Dofus')
@section('description', '')
@php($bodyClass = 'home-page')
@php($activePage = 'index')

@section('content')
<section class="home-hero">
            <div class="container home-hero-grid">
                <div class="home-hero-content">
                    <h1>Bienvenue sur le site des <span>Z&#39;Heros</span> !</h1>
                    <p>Chers mercenaires, l&#39;accueil garde les infos à consulter vite: Almanax et annonces de guilde. Les parties plus pratiques, comme les sorties et les missions, ont maintenant leur propre page pour éviter les grands allers-retours.</p>
                    <div class="home-hero-actions">
                        <a class="btn btn--primary" href="{{ route('sorties.index') }}">Voir les sorties</a>
                        <a class="btn btn--outline" href="{{ route('missions.index') }}">Voir les missions</a>
                    </div>
                </div>

                <div class="home-hero-media">
                    <img class="home-hero-image" src="{{ asset('assets/img/divers/hall-guilde.png') }}" alt="Hall de guilde">
                </div>
            </div>
        </section>

        <section class="home-overview">
            <div class="container home-overview-grid">
                <section class="home-almanax" id="almanax">
                    <div class="home-section-head home-section-head--split home-section-head--compact">
                        <div>
                            <h2>Almanax des <span>2 semaines</span></h2>
                        </div>
                    </div>

                    <section class="almanax-shell" aria-label="Almanax des 2 semaines" data-almanax data-almanax-language="fr" data-almanax-timezone="Europe/Paris" data-almanax-days="14" data-almanax-level="200">
                        <div class="almanax-shell__top">
                            <div class="almanax-shell__headline">
                                <span class="guild-news-tag guild-news-tag--event">Almanax en direct</span>
                                <h3 data-almanax-range hidden>Semaine en cours</h3>
                            </div>

                            <div class="almanax-shell__status" aria-live="polite">
                                <strong data-almanax-current>1</strong>
                                <span data-almanax-total>/7</span>
                            </div>
                        </div>

                        <div class="almanax-tabs" data-almanax-tabs aria-label="Jours de l&#39;almanax"></div>
                        <div class="almanax-panel" data-almanax-panel aria-live="polite"></div>
                        <p class="almanax-feedback" data-almanax-feedback hidden>Chargement de l&#39;Almanax...</p>
                    </section>
                </section>

                <section class="home-news" id="annonces">
                    <div class="home-section-head">
                        <h2>Annonces de <span>guilde</span></h2>
                    </div>

                    @if($announcements->isNotEmpty())
                        @php($featuredAnnouncement = $announcements->first())
                        @php($featuredTagClass = $featuredAnnouncement->tag === 'maintenance' ? 'logistique' : $featuredAnnouncement->tag)
                        <div class="guild-news-layout">
                            <article class="guild-news-featured guild-news-featured--{{ $featuredTagClass }}">
                                <div class="guild-news-meta-row">
                                    <span class="guild-news-tag guild-news-tag--{{ $featuredTagClass }}">{{ $featuredAnnouncement->tagLabel() }}</span>
                                    <time datetime="{{ $featuredAnnouncement->published_at?->toDateString() }}">{{ $featuredAnnouncement->published_at?->translatedFormat('j M Y') }}</time>
                                </div>
                                <h3>{{ $featuredAnnouncement->title }}</h3>
                                <p>{{ $featuredAnnouncement->preview() }}</p>
                                @if($featuredAnnouncement->hasReadMore())
                                    <button class="guild-news-link guild-news-link--button" type="button" aria-haspopup="dialog" aria-controls="news-modal" data-news-source="news-{{ $featuredAnnouncement->id }}">Voir plus</button>
                                @endif
                            </article>

                            <div class="guild-news-stack">
                                @foreach($announcements->skip(1) as $announcement)
                                    @php($tagClass = $announcement->tag === 'maintenance' ? 'logistique' : $announcement->tag)
                                    <article class="guild-news-card guild-news-card--{{ $tagClass }}">
                                        <div class="guild-news-meta-row">
                                            <span class="guild-news-tag guild-news-tag--{{ $tagClass }}">{{ $announcement->tagLabel() }}</span>
                                            <time datetime="{{ $announcement->published_at?->toDateString() }}">{{ $announcement->published_at?->translatedFormat('j M Y') }}</time>
                                        </div>
                                        <h3>{{ $announcement->title }}</h3>
                                        <p>{{ $announcement->preview() }}</p>
                                        @if($announcement->hasReadMore())
                                            <button class="guild-news-link guild-news-link--button" type="button" aria-haspopup="dialog" aria-controls="news-modal" data-news-source="news-{{ $announcement->id }}">Voir plus</button>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </div>

                        @foreach($announcements as $announcement)
                            <div class="news-modal-source" id="news-{{ $announcement->id }}" hidden>{!! $announcement->formattedContent() !!}</div>
                        @endforeach
                    @else
                        <p class="stuff-empty">Aucune annonce publiée pour le moment.</p>
                    @endif
                </section>
            </div>
        </section>
@endsection

@section('after_main')
<div class="news-modal" id="news-modal" hidden>
        <div class="news-modal__backdrop" data-news-close></div>
        <div class="news-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="news-modal-title">
            <button class="news-modal__close" type="button" aria-label="Fermer l'annonce" data-news-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="news-modal__meta">
                <span class="guild-news-tag news-modal__tag" id="news-modal-tag"></span>
                <time id="news-modal-date"></time>
            </div>
            <h3 id="news-modal-title"></h3>
            <div class="news-modal__content" id="news-modal-content"></div>
        </div>
    </div>
@endsection
