@extends('layouts.front')

@section('title', 'Missions | Les Zheros')
@section('description', 'Missions de guilde disponibles pour Les Zheros.')
@php($bodyClass = 'home-page')
@php($activePage = 'missions')

@section('content')
<section class="page-hero">
    <div class="container page-hero__content">
        <h1>Missions de <span>guilde</span></h1>
        <p>Les missions disponibles sont séparées de l'accueil pour comparer les objectifs plus tranquillement.</p>
    </div>
</section>

<section class="home-missions">
    <div class="container">
        <div class="mission-card-grid">
            @forelse ($missions as $mission)
                <article class="mission-card {{ $mission->cardClass() }}">
                    <div class="mission-card-badge"><img src="{{ asset($mission->badgePath()) }}" alt=""></div>
                    <span class="mission-card-type {{ $mission->typeClass() }}">{{ $mission->categoryLabel() }}</span>
                    <h3>{{ $mission->title }}</h3>
                    @if ($mission->description() !== '')
                        <p>{!! $mission->description() !!}</p>
                    @endif
                    <img src="{{ $mission->imageUrl() }}" alt="{{ $mission->title }}">
                </article>
            @empty
                <div class="admin-empty-state admin-empty-state--panel">
                    <strong>Aucune mission disponible</strong>
                    <span>Les prochaines missions apparaîtront ici dès qu'elles seront ajoutées.</span>
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
