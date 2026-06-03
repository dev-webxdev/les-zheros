@extends('layouts.front')

@section('title', 'Galerie | Les Zheros')
@section('description', 'Galerie de guilde Les Zheros.')
@php
    $bodyClass = 'home-page gallery-page';
    $activePage = 'galerie';
@endphp

@push('scripts')
<script src="{{ asset('assets/js/front/gallery.js') }}?v={{ filemtime(public_path('assets/js/front/gallery.js')) }}" defer></script>
@endpush

@section('content')
<section class="page-hero gallery-hero">
    <div class="container page-hero__content">
        <span class="section-kicker">Souvenirs de guilde</span>
        <h1>Galerie de <span>guilde</span></h1>
        <p>Les screens marquants, les halls, les sorties propres et les petits moments qui méritent de rester au chaud.</p>
    </div>
</section>

<section class="gallery-catalog" data-gallery-root>
    <div class="container">
        @if($images->isNotEmpty())
            <div class="gallery-grid">
                @foreach($images as $image)
                    @php
                        $description = $image->description ?: 'Aucune description ajoutée.';
                        $hasReadMore = $image->description && \Illuminate\Support\Str::length($image->description) > 220;
                    @endphp
                    <article class="gallery-card">
                        <button class="gallery-card__media" type="button" data-gallery-open data-gallery-src="{{ $image->imageUrl() }}" data-gallery-title="{{ $image->title }}" data-gallery-description="{{ $image->description }}" data-gallery-date="{{ $image->displayDate() }}">
                            <img src="{{ $image->imageUrl() }}" alt="{{ $image->title }}" loading="lazy">
                        </button>
                        <div class="gallery-card__body">
                            <h2>{{ $image->title }}</h2>
                            <p>{{ $hasReadMore ? \Illuminate\Support\Str::limit($description, 220) : $description }}</p>
                            @if($hasReadMore)
                                <button class="gallery-card__read-more" type="button" data-gallery-open data-gallery-src="{{ $image->imageUrl() }}" data-gallery-title="{{ $image->title }}" data-gallery-description="{{ $image->description }}" data-gallery-date="{{ $image->displayDate() }}">Lire la suite</button>
                            @endif
                            <time datetime="{{ $image->dateValue() }}">{{ $image->displayDate() }}</time>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p class="stuff-empty">Aucune image publiée pour le moment.</p>
        @endif
    </div>

    <div class="gallery-lightbox" data-gallery-modal hidden>
        <button class="gallery-lightbox__backdrop" type="button" data-gallery-close aria-label="Fermer"></button>
        <section class="gallery-lightbox__dialog" role="dialog" aria-modal="true" aria-labelledby="gallery-modal-title">
            <button class="gallery-lightbox__close" type="button" data-gallery-close>Fermer</button>
            <img src="" alt="" data-gallery-modal-image>
            <div class="gallery-lightbox__caption">
                <h2 id="gallery-modal-title" data-gallery-modal-title></h2>
                <p data-gallery-modal-description></p>
                <time data-gallery-modal-date></time>
            </div>
        </section>
    </div>
</section>
@endsection
