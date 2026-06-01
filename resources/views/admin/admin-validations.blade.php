@extends('layouts.admin')

@section('title', 'Validations | Les Zheros')
@section('description', 'Administration des validations de missions de la guilde Les Zheros.')
@php($activeAdmin = 'admin-validations')
@php($statusLabels = \App\Models\MissionValidation::STATUSES)
@php($filters = $filters ?? ['search' => '', 'player' => 'all', 'status' => 'all'])
@php($statusIcons = [
    \App\Models\MissionValidation::PENDING => 'fa-regular fa-clock',
    \App\Models\MissionValidation::VALIDATED => 'fa-solid fa-check',
    \App\Models\MissionValidation::REFUSED => 'fa-solid fa-xmark',
])
@push('scripts')
<script src="{{ asset('assets/js/admin-validations.js') }}" defer></script>
@endpush

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Validations</p>
        </div>

        <div class="admin-actions">
            <label class="admin-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Rechercher..." form="validation-filter-form" data-validation-filter="search" data-validation-server-filter>
            </label>
            <a class="admin-secondary-button" href="{{ route('admin.validations.trash') }}">
                <i class="fa-regular fa-trash-can"></i>
                <span>Corbeille</span>
            </a>
            <a class="admin-create-button" href="{{ route('admin.validations.create') }}">
                <i class="fa-solid fa-circle-plus"></i>
                <span>Ajouter une déclaration</span>
            </a>
        </div>
    </header>

    <section class="admin-content">
        <div class="admin-title admin-title--split">
            <div>
                <i class="fa-solid fa-circle-check"></i>
                <h1>Validations joueurs</h1>
            </div>
            <p>Contrôle les déclarations de missions, vérifie les preuves et garde l'historique.</p>
        </div>

        <section class="admin-validation-stats" aria-label="Résumé des validations">
            <article><span>En attente</span><strong>{{ $stats['pending'] }}</strong></article>
            <article><span>Validées</span><strong>{{ $stats['validated'] }}</strong></article>
            <article><span>Refusées</span><strong>{{ $stats['refused'] }}</strong></article>
            <article><span>Points validés</span><strong>{{ $stats['points'] }}</strong></article>
        </section>

        <form id="validation-filter-form" class="admin-validation-filters" method="get" action="{{ route('admin.validations.index') }}" aria-label="Filtres des validations">
            <label class="admin-field" for="validation-player">
                <span>Joueur</span>
                <select id="validation-player" name="player" data-validation-filter="player" data-validation-server-filter>
                    <option value="all" @selected($filters['player'] === 'all')>Tous les joueurs</option>
                    @foreach ($players as $player)
                        <option value="{{ $player->name }}" @selected($filters['player'] === $player->name)>{{ $player->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="admin-field" for="validation-status">
                <span>Statut</span>
                <select id="validation-status" name="status" data-validation-filter="status" data-validation-server-filter>
                    <option value="all" @selected($filters['status'] === 'all')>Tous</option>
                    @foreach ($statusLabels as $status => $label)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </form>

        @include('admin.partials.bulk-actions', [
            'id' => 'validations-bulk-form',
            'action' => route('admin.validations.bulk'),
            'actions' => [
                \App\Models\MissionValidation::VALIDATED => 'Valider',
                \App\Models\MissionValidation::REFUSED => 'Refuser',
                \App\Models\MissionValidation::PENDING => 'Remettre en attente',
                'trash' => 'Corbeille',
            ],
            'label' => 'Choisir une action',
        ])

        <section class="admin-validation-layout">
            <div class="admin-table-card admin-validation-table-card">
                <table class="admin-table admin-table--validations">
                    <thead>
                        <tr>
                            <th class="admin-bulk-check"><input type="checkbox" data-bulk-check-all="validations-bulk-form" aria-label="Tout sélectionner"></th>
                            <th>Joueur</th>
                            <th>Mission</th>
                            <th>Aide</th>
                            <th>Persos</th>
                            <th>Points</th>
                            <th>Preuve</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($validations as $validation)
                            @php($proofSource = $validation->proof_path ?: ($validation->proof_text && filter_var($validation->proof_text, FILTER_VALIDATE_URL) ? $validation->proof_text : ''))
                            <tr data-validation-row data-player="{{ $validation->user?->name }}" data-status="{{ $validation->status }}" data-search="{{ $validation->user?->name }} {{ $validation->mission?->title }}">
                                <td class="admin-bulk-check"><input type="checkbox" name="ids[]" value="{{ $validation->id }}" form="validations-bulk-form" data-bulk-item aria-label="Sélectionner la validation de {{ $validation->user?->name }}"></td>
                                <td>
                                    <div class="admin-user-cell">
                                        <span class="admin-user-avatar">
                                            @if ($validation->user?->avatarUrl())
                                                <img src="{{ $validation->user->avatarUrl() }}" alt="Photo de {{ $validation->user->name }}">
                                            @else
                                                {{ $validation->user?->initials() ?? 'US' }}
                                            @endif
                                        </span>
                                        <strong>{{ $validation->user?->name ?? 'Utilisateur supprimé' }}</strong>
                                    </div>
                                </td>
                                <td><strong>{{ $validation->mission?->title ?? 'Mission supprimée' }}</strong></td>
                                <td>{{ filled($validation->teammates) ? 'Oui' : 'Non' }}</td>
                                <td>{{ $validation->characters }}</td>
                                <td>
                                    <strong>{{ number_format($validation->estimatedPoints(), 2) }}</strong>
                                    @if ($validation->status !== \App\Models\MissionValidation::VALIDATED)
                                        <span>{!! $validation->isRepeatEstimate() ? 'refaite' : 'pr&eacute;vu' !!}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($proofSource)
                                        <button class="admin-secondary-button" type="button" data-proof-src="{{ $proofSource }}" data-proof-player="{{ $validation->user?->name }}" data-proof-mission="{{ $validation->mission?->title }}"><span>Voir</span></button>
                                    @elseif ($validation->proof_text)
                                        <span class="admin-muted-text">{{ \Illuminate\Support\Str::limit($validation->proof_text, 30) }}</span>
                                    @else
                                        <span class="admin-muted-text">Aucune</span>
                                    @endif
                                </td>
                                <td><span @class(['admin-tag', $validation->statusTagClass()])>{{ $validation->statusLabel() }}</span></td>
                                <td><time datetime="{{ $validation->created_at?->toIso8601String() }}">{{ $validation->created_at?->translatedFormat('d M H:i') }}</time></td>
                                <td>
                                    <div class="admin-row-actions">
                                        @if ($validation->status !== \App\Models\MissionValidation::VALIDATED)
                                            <form action="{{ route('admin.validations.status', $validation) }}" method="post" data-real-form>
                                                @csrf
                                                @method('patch')
                                                <input type="hidden" name="status" value="{{ \App\Models\MissionValidation::VALIDATED }}">
                                                <button class="admin-action-button admin-action-button--confirm" type="submit" aria-label="Valider la déclaration de {{ $validation->user?->name }}" title="Valider">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <details class="admin-action-menu">
                                            <summary aria-label="Options de la validation" title="Options">
                                                <i class="fa-solid fa-ellipsis"></i>
                                                <span>Actions</span>
                                            </summary>
                                            <div>
                                                <a href="{{ route('admin.validations.edit', $validation) }}"><i class="fa-regular fa-pen-to-square"></i> Modifier</a>
                                                @foreach ($statusLabels as $status => $label)
                                                    @continue($status === \App\Models\MissionValidation::VALIDATED)
                                                    <form action="{{ route('admin.validations.status', $validation) }}" method="post" data-real-form>
                                                        @csrf
                                                        @method('patch')
                                                        <input type="hidden" name="status" value="{{ $status }}">
                                                        <button type="submit"><i class="{{ $statusIcons[$status] ?? 'fa-regular fa-circle' }}"></i> {{ $label }}</button>
                                                    </form>
                                                @endforeach
                                                <form action="{{ route('admin.validations.destroy', $validation) }}" method="post" data-real-form>
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit" class="is-danger"><i class="fa-regular fa-trash-can"></i> Corbeille</button>
                                                </form>
                                            </div>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="admin-empty-state">
                                        <strong>Aucune validation</strong>
                                        <span>Les déclarations envoyées depuis le profil apparaîtront ici.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @include('partials.admin-pagination', ['paginator' => $validations])
            </div>

            <aside class="admin-proof-panel" data-proof-viewer>
                <div class="admin-proof-panel__head">
                    <span>Preuve sélectionnée</span>
                    <strong data-proof-title>Choisis une preuve</strong>
                </div>
                <button class="admin-proof-preview" type="button" data-proof-open aria-label="Agrandir la preuve">
                    <img src="{{ asset('assets/img/card-mission/type.png') }}" alt="Preuve de validation" data-proof-image>
                    <span><i class="fa-solid fa-magnifying-glass-plus"></i> Agrandir</span>
                </button>
                <dl>
                    <div><dt>Joueur</dt><dd data-proof-player>-</dd></div>
                    <div><dt>Mission</dt><dd data-proof-mission>-</dd></div>
                </dl>
            </aside>
        </section>
    </section>
</main>
@endsection

@section('modals')
<div class="admin-proof-modal" data-proof-modal hidden>
    <div class="admin-proof-modal__backdrop" data-proof-close></div>
    <section class="admin-proof-modal__dialog" role="dialog" aria-modal="true" aria-label="Preuve en grand">
        <button class="admin-proof-modal__close" type="button" data-proof-close aria-label="Fermer la preuve">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <img src="{{ asset('assets/img/card-mission/type.png') }}" alt="Preuve de validation en grand" data-proof-modal-image>
    </section>
</div>
@endsection
