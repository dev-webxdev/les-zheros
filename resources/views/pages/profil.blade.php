@extends('layouts.front')

@section('title', 'Les Zheros | Guilde Dofus')
@section('description', 'Espace profil Les Zheros pour gérer le compte, les missions et les statistiques de participation.')
@php($bodyClass = 'profile-page-body')
@php($activePage = 'profil')
@php($user = auth()->user())

@push('styles')
<link rel="stylesheet" href="{{ $versionedAsset('assets/css/profil.css') }}">
@endpush

@section('content')
@php($roleLabels = \App\Support\AdminAccess::roles())
@php($userRoles = $user?->adminRoles() ?? [\App\Support\AdminAccess::MEMBER])
@php($profileRoles = \App\Support\AdminAccess::displayRoles($userRoles))
@php($missions = $missions ?? collect())
@php($missionValidations = $missionValidations ?? collect())
@php($teammates = $teammates ?? collect())
@php($profileStats = $profileStats ?? ['completedOutings' => 0, 'participationRate' => 0, 'guildMissions' => 0, 'missionPoints' => 0, 'months' => []])
@php($profileChartMonths = collect($profileStats['months'])->values())
@php($profileChartMax = max(1, $profileChartMonths->max(fn ($month) => ($month['missions'] ?? 0) + ($month['outings'] ?? 0)) ?? 1))
@php($profileChartCount = max(1, $profileChartMonths->count() - 1))
@php($profileChartPoints = $profileChartMonths->map(function ($month, $index) use ($profileChartMax, $profileChartCount) {
    $x = 8 + (($index / $profileChartCount) * 184);
    $value = ($month['missions'] ?? 0) + ($month['outings'] ?? 0);
    $y = 92 - (($value / $profileChartMax) * 72);

    return ['x' => round($x, 2), 'y' => round($y, 2), 'value' => $value, 'label' => $month['label']];
}))
@php($profileLinePoints = $profileChartPoints->map(fn ($point) => $point['x'].','.$point['y'])->join(' '))
@php($profileAreaPoints = '8,96 '.$profileLinePoints.' 192,96')
@php($profileRateOffset = 251.2 - (251.2 * min(100, max(0, $profileStats['participationRate'])) / 100))
@php($profileRoleClasses = [
    \App\Support\AdminAccess::ADMIN => 'rank--meneur',
    \App\Support\AdminAccess::MODERATOR => 'rank--bras-droit',
    \App\Support\AdminAccess::MISSION_MASTER => 'rank--tresorier',
    \App\Support\AdminAccess::EDITOR => 'rank--recruteur',
    \App\Support\AdminAccess::ILLUSTRATOR => 'rank--illustrateur',
    \App\Support\AdminAccess::MEMBER => 'rank--guildeux',
])
@php($profileRoleIcons = [
    \App\Support\AdminAccess::ADMIN => 'fa-crown',
    \App\Support\AdminAccess::MODERATOR => 'fa-shield-halved',
    \App\Support\AdminAccess::MISSION_MASTER => 'fa-scroll',
    \App\Support\AdminAccess::EDITOR => 'fa-pen-nib',
    \App\Support\AdminAccess::ILLUSTRATOR => 'fa-images',
    \App\Support\AdminAccess::MEMBER => 'fa-shield-halved',
])
<section class="profile-hero">
    <div class="container">
        <div class="profile-hero-card">
            <div class="profile-avatar profile-avatar--hero" data-profile-avatar-preview>
                @if ($user?->avatarUrl())
                    <img src="{{ $user->avatarUrl() }}" alt="Photo de profil de {{ $user->name }}">
                @else
                    <span>{{ $user?->initials() ?? 'J' }}</span>
                @endif
            </div>
            <div>
                <h1 class="profile-hero-title" data-profile-page-title>Mon profil</h1>
                <div class="profile-hero-meta">
                    @foreach ($profileRoles as $role)
                        <span class="guild-rank-badge {{ $profileRoleClasses[$role] ?? 'rank--guildeux' }}">
                            <i class="fa-solid {{ $profileRoleIcons[$role] ?? 'fa-shield-halved' }}"></i>
                            {{ $roleLabels[$role] ?? $role }}
                        </span>
                    @endforeach
                    <span class="profile-member-since">Bienvenue {{ $user?->name ?? 'aventurier' }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

<nav class="profile-tabs" aria-label="Navigation du profil">
    <div class="container">
        <ul>
            <li class="is-active"><a href="#profile-panel" data-profile-tab><i class="fa-regular fa-user"></i><span>Informations</span></a></li>
            <li><a href="#missions-panel" data-profile-tab><i class="fa-solid fa-paper-plane"></i><span>Missions</span></a></li>
            <li><a href="#stats-panel" data-profile-tab><i class="fa-solid fa-chart-column"></i><span>Statistiques</span></a></li>
        </ul>
    </div>
</nav>

<div class="profile-page" data-profile-tabs-root>
        <section id="profile-panel" class="profile-tab-panel is-active" role="tabpanel">
            <h2 class="section-title"><i class="fa-regular fa-user"></i> Mes informations</h2>

            <form action="{{ route('profil.update') }}" method="post" enctype="multipart/form-data" class="form-block" data-real-form>
                @csrf
                @method('patch')
                <div class="panel profile-avatar-editor">
                    <div class="profile-avatar profile-avatar--editor" data-profile-avatar-preview>
                        @if ($user?->avatarUrl())
                            <img src="{{ $user->avatarUrl() }}" alt="Photo de profil de {{ $user->name }}">
                        @else
                            <span>{{ $user?->initials() ?? 'J' }}</span>
                        @endif
                    </div>
                    <div class="profile-avatar-editor__content">
                        <h3>Photo de profil</h3>
                        <p>Image carree conseillee, 4 Mo maximum.</p>
                        <div class="profile-avatar-editor__actions">
                            <label class="btn btn--outline" for="profile-avatar">Choisir une photo</label>
                            <input id="profile-avatar" class="profile-avatar-editor__input" type="file" name="avatar" accept="image/*" data-profile-avatar-input data-profile-avatar-url="{{ route('profil.avatar.update') }}">
                        </div>
                        <span class="profile-avatar-editor__filename" data-profile-avatar-name hidden></span>
                    </div>
                </div>

                <div class="panel form-grid form-grid--3">
                    <div class="field">
                        <label for="profile-email">Email</label>
                        <input id="profile-email" type="email" name="email" value="{{ old('email', $user?->email) }}" required>
                    </div>
                    <div class="field">
                        <label for="profile-username">Pseudo</label>
                        <input id="profile-username" type="text" name="name" value="{{ old('name', $user?->name) }}" required>
                    </div>
                    <div class="field">
                        <label for="profile-country">Pays</label>
                        <select id="profile-country" name="country">
                            <option value="fr" @selected(old('country', $user?->country ?? 'fr') === 'fr')>France</option>
                            <option value="es" @selected(old('country', $user?->country ?? 'fr') === 'es')>Espagne</option>
                            <option value="pt" @selected(old('country', $user?->country ?? 'fr') === 'pt')>Portugal</option>
                        </select>
                    </div>
                </div>

                <div class="action-row form-actions">
                    <button type="submit" class="btn btn--primary">Modifier mon profil</button>
                </div>
            </form>

            <h2 class="section-title"><i class="fa-solid fa-key"></i> Mot de passe</h2>

            <form action="{{ route('profil.password.update') }}" method="post" class="form-block" data-real-form>
                @csrf
                @method('patch')
                <div class="panel form-grid form-grid--2">
                    <div class="field">
                        <label for="profile-new-password">Nouveau mot de passe</label>
                        <input id="profile-new-password" type="password" name="password" minlength="6" autocomplete="new-password" required>
                    </div>
                    <div class="field">
                        <label for="profile-confirm-password">Confirmer le mot de passe</label>
                        <input id="profile-confirm-password" type="password" name="password_confirmation" minlength="6" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="action-row form-actions">
                    <button type="submit" class="btn btn--primary">Modifier mon mot de passe</button>
                </div>
            </form>

            <section class="content-section profile-security-block profile-security-block--danger">
                <div class="profile-security-content">
                    <h2 class="section-title profile-security-title profile-security-title--danger"><i class="fa-solid fa-triangle-exclamation"></i> Zone de non retour</h2>
                    <p class="profile-security-text">Tu peux demander la suppression de ton compte. Il sera place en corbeille en attendant le traitement par un administrateur.</p>
                    <p class="profile-security-text">Si c'est une erreur, un admin pourra restaurer le compte avant suppression definitive.</p>
                </div>
                <form action="{{ route('profil.destroy') }}" method="post" class="action-row" data-delete-account-form onsubmit="return confirm('Envoyer une demande de suppression ? Ton compte sera place en corbeille en attendant le traitement par un administrateur.');">
                    @csrf
                    @method('delete')
                    <button type="submit" class="btn btn--danger"><i class="fa-solid fa-trash-can"></i><span>Demander la suppression</span></button>
                </form>
            </section>
        </section>

        <section id="missions-panel" class="profile-tab-panel" role="tabpanel" hidden>
            <h2 class="section-title"><i class="fa-solid fa-paper-plane"></i> Declaration de mission</h2>

            <form action="{{ route('profil.missions.store') }}" method="post" enctype="multipart/form-data" class="form-block mission-form" data-mission-form data-real-form>
                @csrf
                <p class="helper-text">Choisis la mission faite, indique le nombre de personnages et ajoute une preuve si tu en as une.</p>

                <div class="panel form-grid form-grid--1">
                    <div class="form-grid form-grid--2 form-grid--nested">
                        <div class="field">
                            <label for="mission-name">Mission</label>
                            <select id="mission-name" name="mission_name">
                                <option value="">Choisir une mission</option>
                                @foreach ($missions as $mission)
                                    <option value="{{ $mission->id }}">
                                        {{ $mission->title }}
                                        @if ($mission->category === 'songe')
                                            - {{ $mission->dreamTypeLabel() }} / Palier {{ $mission->dream_floor }}
                                        @else
                                            - {{ $mission->categoryLabel() }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="mission-characters">Persos utilises</label>
                            <input id="mission-characters" type="number" name="mission_characters" value="1" min="1" max="8">
                        </div>
                    </div>

                    <label class="check mission-teammates-toggle-card">
                        <input type="checkbox" name="has_teammates" data-mission-teammates-toggle>
                        <span>
                            <strong>J'ai des coéquipiers</strong>
                        </span>
                    </label>

                    <div class="mission-teammates" data-mission-teammates hidden>
                        <div class="mission-teammates-list" data-mission-teammates-list>
                            <div class="mission-teammate-row">
                                <div class="field">
                                    <label>Coequipier</label>
                                    <select name="teammate_name[]">
                                        <option value="">Choisir un coequipier</option>
                                        @foreach ($teammates as $teammate)
                                            <option value="{{ $teammate->id }}">{{ $teammate->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="field">
                                    <label>Persos coequipier</label>
                                    <input type="number" name="teammate_characters[]" value="1" min="1" max="8">
                                </div>

                                <button type="button" class="btn btn--outline mission-remove-button" data-remove-teammate hidden>Retirer</button>
                            </div>
                        </div>

                        <button type="button" class="btn btn--outline mission-add-button" data-add-teammate>Ajouter un coequipier</button>
                    </div>

                    <label class="mission-file-field" for="mission-proof-file">
                        <span><i class="fa-regular fa-image"></i> Screen de fin optionnel</span>
                        <strong data-mission-file-name>Choisir un fichier</strong>
                        <input id="mission-proof-file" type="file" name="proof_file" accept="image/*" data-mission-file-input>
                    </label>
                </div>

                <div class="action-row form-actions">
                    <button type="submit" class="btn btn--primary">Envoyer au meneur</button>
                </div>
            </form>

            <h2 class="section-title"><i class="fa-solid fa-clipboard-check"></i> Missions terminees</h2>

            <section class="content-section panel">
                @forelse ($missionValidations as $validation)
                    <div class="list-row">
                        <div class="list-item-info">
                            <img src="{{ $validation->mission?->imageUrl() }}" alt="{{ $validation->mission?->title }}">
                            <div class="list-item-info-text">
                                <h3 class="list-title">{{ $validation->mission?->title ?? 'Mission supprimée' }}</h3>
                                <p class="list-text">{{ $validation->created_at?->translatedFormat('d M Y H:i') }} · {{ $validation->characters }} perso(s)</p>
                            </div>
                        </div>
                        <span class="pill {{ $validation->frontPillClass() }}">{{ $validation->statusLabel() }}</span>
                    </div>
                @empty
                    <div class="list-row">
                        <div class="list-item-info-text">
                            <h3 class="list-title">Aucune mission terminée</h3>
                            <p class="list-text">Tes déclarations apparaîtront ici après envoi.</p>
                        </div>
                    </div>
                @endforelse
            </section>
        </section>

        <section id="stats-panel" class="profile-tab-panel" role="tabpanel" hidden>
            <h2 class="section-title"><i class="fa-solid fa-chart-column"></i> Statistiques</h2>

            <div class="stats-grid">
                <article class="stat-card">
                    <span class="stat-label">Missions de guilde</span>
                    <strong class="stat-value">{{ $profileStats['guildMissions'] }}</strong>
                </article>

                <article class="stat-card">
                    <span class="stat-label">Sorties réalisées</span>
                    <strong class="stat-value">{{ $profileStats['completedOutings'] }}</strong>
                </article>

                <article class="stat-card">
                    <span class="stat-label">Taux de participation</span>
                    <strong class="stat-value">{{ $profileStats['participationRate'] }}%</strong>
                </article>
            </div>

            <section class="profile-stats-board" aria-label="Graphiques des statistiques">
                <article class="profile-chart-card profile-chart-card--wide">
                    <div class="profile-chart-card__head">
                        <div>
                            <span>Progression</span>
                            <h3>Activité de guilde</h3>
                        </div>
                        <div class="profile-chart-legend">
                            <span><i class="profile-chart-dot profile-chart-dot--activity"></i>Missions + sorties</span>
                        </div>
                    </div>

                    <div class="profile-area-chart">
                        <svg viewBox="0 0 200 110" role="img" aria-label="Activité de guilde sur les derniers mois" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="profile-area-fill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#ffcf69" stop-opacity=".38"></stop>
                                    <stop offset="100%" stop-color="#ffcf69" stop-opacity="0"></stop>
                                </linearGradient>
                            </defs>
                            <g class="profile-area-chart__grid">
                                <line x1="8" y1="20" x2="192" y2="20"></line>
                                <line x1="8" y1="44" x2="192" y2="44"></line>
                                <line x1="8" y1="68" x2="192" y2="68"></line>
                                <line x1="8" y1="92" x2="192" y2="92"></line>
                            </g>
                            <polygon class="profile-area-chart__fill" points="{{ $profileAreaPoints }}"></polygon>
                            <polyline class="profile-area-chart__line" points="{{ $profileLinePoints }}"></polyline>
                            @foreach($profileChartPoints as $pointIndex => $point)
                                <g class="profile-area-chart__marker profile-area-chart__marker--{{ $pointIndex + 1 }}">
                                    <circle class="profile-area-chart__hit" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5"></circle>
                                    <circle class="profile-area-chart__point" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="1.8"></circle>
                                </g>
                            @endforeach
                        </svg>
                        <div class="profile-area-chart__tooltips" aria-hidden="true">
                            @foreach($profileChartPoints as $point)
                                <span style="left: {{ (($point['x'] - 8) / 184) * 100 }}%; top: {{ $point['y'] }}%;">
                                    <strong>{{ $point['value'] }}</strong>
                                    <small>{{ $point['label'] }}</small>
                                </span>
                            @endforeach
                        </div>
                        <div class="profile-area-chart__labels">
                            @foreach($profileChartPoints as $point)
                                <span title="{{ $point['value'] }} activité{{ $point['value'] > 1 ? 's' : '' }}">{{ $point['label'] }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="profile-chart-breakdown">
                        @foreach($profileChartMonths as $month)
                            <div>
                                <span>{{ $month['label'] }}</span>
                                <strong>{{ ($month['missions'] ?? 0) + ($month['outings'] ?? 0) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="profile-chart-card profile-chart-card--rate">
                    <div class="profile-chart-card__head">
                        <div>
                            <span>Participation</span>
                            <h3>Sorties validées</h3>
                        </div>
                    </div>
                    <div class="profile-rate-chart" style="--profile-rate-offset: {{ $profileRateOffset }}">
                        <svg viewBox="0 0 100 100" role="img" aria-label="Taux de participation {{ $profileStats['participationRate'] }}%">
                            <circle class="profile-rate-chart__track" cx="50" cy="50" r="40"></circle>
                            <circle class="profile-rate-chart__value" cx="50" cy="50" r="40"></circle>
                        </svg>
                        <div>
                            <strong>{{ $profileStats['participationRate'] }}%</strong>
                            <span>{{ $profileStats['completedOutings'] }} sortie{{ $profileStats['completedOutings'] > 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                </article>

                <article class="profile-chart-card profile-chart-card--points">
                    <div class="profile-chart-card__head">
                        <div>
                            <span>Contribution</span>
                            <h3>Points missions</h3>
                        </div>
                    </div>
                    <strong class="profile-points-value">{{ rtrim(rtrim(number_format($profileStats['missionPoints'], 2, ',', ' '), '0'), ',') }}</strong>
                    <p>Points validés grâce aux missions de guilde.</p>
                </article>
            </section>
        </section>
</div>
@endsection
