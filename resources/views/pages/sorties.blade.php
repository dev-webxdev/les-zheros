@extends('layouts.front')

@section('title', 'Sorties | Les Zheros')
@section('description', 'Inscriptions aux sorties de guilde Les Zheros.')
@php
    $bodyClass = 'home-page outings-page';
    $activePage = 'sorties';
@endphp

@section('content')
<section class="page-hero">
    <div class="container page-hero__content">
        <h1>Sorties de <span>guilde</span></h1>
        <p>Vote pour ton jour et ton horaire sans descendre sous les annonces ou les missions.</p>
    </div>
</section>

<section class="home-outings">
    <div class="container">
        @forelse ($outings as $outing)
            @php
                $userVote = $outing->voteFor(auth()->user());
                $votesBySlot = $outing->votes->groupBy('slot_id');
                $firstDay = collect($outing->schedule)->first();
                $confirmedSlot = $outing->confirmedSlotDetails();
                $confirmedVotes = $outing->confirmedVotes();
                $memberName = auth()->user()?->name ?? 'Invité';
            @endphp
            <section class="guild-vote-shell" aria-label="Sortie de mission de guilde" data-guild-vote data-member-name="{{ $memberName }}">
                <div class="guild-vote-feedback" data-guild-vote-feedback role="status">
                    @if($confirmedSlot)
                        Cette sortie est validée. Voici le créneau retenu et les joueurs à inviter.
                    @else
                    @auth
                        {{ $userVote ? 'Tu es inscrit sur un créneau. Tu peux annuler ton vote si besoin.' : 'Sélectionne un jour puis un créneau horaire pour t’inscrire.' }}
                    @else
                        Connecte-toi pour voter sur cette sortie.
                    @endauth
                    @endif
                </div>

                @if($confirmedSlot)
                    <article class="guild-confirmed-card">
                        <div class="guild-confirmed-card__body">
                            <span class="guild-confirmed-card__eyebrow">Sortie validée</span>
                            <h3>{{ $outing->title }}</h3>
                            <p>{{ $outing->description }}</p>
                        </div>
                        <div class="guild-confirmed-card__slot">
                            <span>{{ \Carbon\Carbon::parse($confirmedSlot['date'])->translatedFormat('l j F') }}</span>
                            <strong>{{ $confirmedSlot['time'] }}</strong>
                        </div>
                        <div class="guild-confirmed-card__players">
                            <strong>Joueurs à inviter</strong>
                            <div>
                                @forelse($confirmedVotes as $vote)
                                    <span>{{ $vote->user?->name ?? 'Membre supprimé' }}</span>
                                @empty
                                    <span>Aucun joueur confirmé.</span>
                                @endforelse
                            </div>
                        </div>
                    </article>
                @else
                <article class="guild-vote-card">
                    <div class="guild-vote-card__head">
                        <div class="guild-vote-card__intro">
                            <h3>{{ $outing->title }}</h3>
                            <p>{{ $outing->description }}</p>
                        </div>
                        <div class="guild-vote-card__meta">
                            <article class="guild-vote-meta-card"><span>Places max</span><strong>{{ $outing->places }} joueurs</strong></article>
                            <article class="guild-vote-meta-card"><span>Inscrits</span><strong data-guild-vote-registered>{{ $outing->votes->count() }}/{{ $outing->places }}</strong></article>
                            <article class="guild-vote-meta-card"><span>Clôture</span><strong>{{ $outing->close_at?->translatedFormat('D j M - H\hi') ?? 'Non définie' }}</strong></article>
                        </div>
                    </div>

                    @if($confirmedSlot)
                        <section class="guild-vote-confirmed">
                            <div>
                                <span class="guild-vote-panel__eyebrow">Sortie validée</span>
                                <h4 class="guild-vote-panel__title">{{ \Carbon\Carbon::parse($confirmedSlot['date'])->translatedFormat('l j F') }} à {{ $confirmedSlot['time'] }}</h4>
                                <p>{{ $confirmedVotes->count() }} joueur{{ $confirmedVotes->count() > 1 ? 's' : '' }} à inviter sur ce créneau.</p>
                            </div>
                            <span class="guild-vote-confirmed__time">{{ $confirmedSlot['time'] }}</span>
                            <div class="guild-vote-confirmed__players">
                                <strong>Joueurs à inviter</strong>
                                @forelse($confirmedVotes as $vote)
                                    <span>{{ $vote->user?->name ?? 'Membre supprimé' }}</span>
                                @empty
                                    <span>Aucun joueur confirmé.</span>
                                @endforelse
                            </div>
                        </section>
                    @else
                    <div class="guild-vote-days" role="tablist" aria-label="Choix du jour">
                        @foreach ($outing->schedule as $dayIndex => $day)
                            @php
                                $dayId = 'sortie-'.$outing->id.'-'.$day['date'];
                            @endphp
                            <button type="button" class="guild-vote-day @if($dayIndex === 0) is-active @endif" data-guild-vote-day="{{ $dayId }}" aria-pressed="{{ $dayIndex === 0 ? 'true' : 'false' }}">
                                <span>{{ \Carbon\Carbon::parse($day['date'])->translatedFormat('l') }}</span>
                                <strong>{{ \Carbon\Carbon::parse($day['date'])->translatedFormat('j M') }}</strong>
                            </button>
                        @endforeach
                    </div>

                    <div class="guild-vote-panels">
                        @foreach ($outing->schedule as $dayIndex => $day)
                            @php
                                $dayId = 'sortie-'.$outing->id.'-'.$day['date'];
                            @endphp
                            <div class="guild-vote-panel @if($dayIndex === 0) is-active @endif" data-guild-vote-panel="{{ $dayId }}" @if($dayIndex !== 0) hidden @endif>
                                <div class="guild-vote-panel__top">
                                    <div>
                                        <span class="guild-vote-panel__eyebrow">Jour sélectionné</span>
                                        <h4 class="guild-vote-panel__title">{{ \Carbon\Carbon::parse($day['date'])->translatedFormat('l j F') }}</h4>
                                    </div>
                                </div>
                                <div class="guild-vote-slot-grid">
                                    @foreach ($day['times'] as $time)
                                        @php
                                            $slotId = $outing->slotId($day['date'], $time);
                                            $slotVotes = $votesBySlot->get($slotId, collect());
                                            $slotMembers = $slotVotes->map(fn ($vote) => $vote->user?->name)->filter()->join('|');
                                            $isConfirmed = $userVote?->slot_id === $slotId;
                                        @endphp
                                        <article class="guild-vote-slot @if($isConfirmed) is-confirmed @endif" data-guild-vote-slot data-slot-id="{{ $slotId }}" data-slot-votes="{{ $slotVotes->count() }}" data-slot-limit="{{ $outing->places }}" data-slot-members="{{ $slotMembers }}" data-slot-confirmed="{{ $isConfirmed ? 'true' : 'false' }}">
                                            <button type="button" class="guild-vote-slot__button" data-guild-vote-select @disabled(!auth()->check() || $outing->isClosed())>
                                                <span class="guild-vote-slot__eyebrow">Départ</span>
                                                <span class="guild-vote-slot__time">{{ $time }}</span>
                                                <span class="guild-vote-slot__count" data-guild-vote-count>{{ $slotVotes->count() }}/{{ $outing->places }} places</span>
                                                <span class="guild-vote-slot__cta">Choisir ce créneau</span>
                                            </button>
                                            <p class="guild-vote-slot__members" data-guild-vote-members>{{ $slotVotes->isNotEmpty() ? $slotVotes->map(fn ($vote) => $vote->user?->name)->filter()->join(', ') : 'Aucun inscrit' }}</p>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="guild-vote-actions">
                        @auth
                            <form action="{{ route('sorties.vote', $outing) }}" method="post" data-real-form>
                                @csrf
                                <input type="hidden" name="slot_id" value="{{ $userVote?->slot_id }}" data-guild-vote-slot-input>
                                <button type="submit" class="btn btn--primary" data-guild-vote-submit @disabled($outing->isClosed())>Valider mon vote</button>
                            </form>
                            <form action="{{ route('sorties.vote.cancel', $outing) }}" method="post" data-real-form>
                                @csrf
                                @method('delete')
                                <button type="submit" class="btn btn--outline" data-guild-vote-cancel @if(!$userVote) hidden @endif>Annuler mon vote</button>
                            </form>
                        @else
                            <a class="btn btn--primary" href="{{ route('connexion') }}">Se connecter pour voter</a>
                        @endauth
                    </div>
                    @endif
                </article>
                @endif
            </section>
        @empty
            <div class="guild-vote-empty">
                <span><i class="fa-solid fa-calendar-xmark"></i></span>
                <h3>Aucune sortie prévue</h3>
                <p>Les prochaines sorties apparaîtront ici quand elles seront publiées.</p>
            </div>
        @endforelse
    </div>
</section>
@endsection
