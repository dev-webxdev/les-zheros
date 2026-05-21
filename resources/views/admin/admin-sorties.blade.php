@extends('layouts.admin')

@section('title', 'Sorties | Les Zheros')
@section('description', 'Gestion des sorties de mission de guilde Les Zheros.')
@php
    $activeAdmin = 'admin-sorties';
    $canDeleteOutings = auth()->user()?->canDeleteInAdminArea('outings');
@endphp

@section('admin')
<main class="admin-main">
    <header class="admin-topbar">
        <div class="admin-breadcrumb">
            <button class="admin-menu-button" type="button" aria-label="Ouvrir la navigation">
                <i class="fa-solid fa-table-columns"></i>
            </button>
            <span></span>
            <p>Sorties</p>
        </div>

        <div class="admin-actions">
            <a class="admin-secondary-button" href="{{ route('admin.sorties.trash') }}">
                <i class="fa-regular fa-trash-can"></i>
                <span>Corbeille</span>
            </a>
            <a class="admin-create-button" href="{{ route('admin.sorties.create') }}">
                <i class="fa-solid fa-plus"></i>
                <span>Créer une sortie</span>
            </a>
        </div>
    </header>

    <section class="admin-content admin-outings">
        <div class="admin-title admin-title--split">
            <div>
                <i class="fa-solid fa-users"></i>
                <h1>Sorties de guilde</h1>
            </div>
            <p>Retrouve les votes publiés, leurs places restantes et les actions rapides.</p>
        </div>

        <div class="admin-table-card">
            <table class="admin-table admin-table--outings admin-table--actions-center">
                <thead>
                    <tr>
                        <th>Sortie</th>
                        <th>Créneaux</th>
                        <th>Inscrits</th>
                        <th>Clôture</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($outings as $outing)
                        <tr>
                            <td>
                                <div class="admin-announcement-cell">
                                    <strong>{{ $outing->title }}</strong>
                                    <span>{{ $outing->description ?: 'Aucune description.' }}</span>
                                </div>
                            </td>
                            <td>{{ $outing->slotCount() }}</td>
                            <td><strong>{{ $outing->votes_count }}/{{ $outing->places }}</strong></td>
                            <td>{{ $outing->close_at?->translatedFormat('D j M, H:i') ?? 'Non définie' }}</td>
                            <td>
                                @if(!$outing->is_published)
                                    <span class="admin-tag">Brouillon</span>
                                @elseif($outing->confirmed_slot_id)
                                    <span class="admin-tag admin-tag--primary">Validée</span>
                                @elseif($outing->isClosed())
                                    <span class="admin-tag admin-tag--danger">Clôturée</span>
                                @else
                                    <span class="admin-tag admin-tag--success">Ouverte</span>
                                @endif
                            </td>
                            <td>
                                <div class="admin-row-actions">
                                    <a class="admin-action-button admin-action-button--edit" href="{{ route('admin.sorties.edit', $outing) }}" aria-label="Modifier {{ $outing->title }}" title="Modifier">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    @php
                                        $confirmableSlots = [];

                                        foreach ($outing->schedule ?? [] as $day) {
                                            foreach ($day['times'] ?? [] as $time) {
                                                $slotId = $outing->slotId($day['date'], $time);
                                                $slotVotes = $outing->votes->where('slot_id', $slotId);

                                                if ($slotVotes->isEmpty()) {
                                                    continue;
                                                }

                                                $confirmableSlots[] = [
                                                    'id' => $slotId,
                                                    'date' => $day['date'],
                                                    'time' => $time,
                                                    'votes' => $slotVotes,
                                                    'names' => $slotVotes->map(fn ($vote) => $vote->user?->name)->filter()->join(', '),
                                                ];
                                            }
                                        }
                                    @endphp
                                    <details class="admin-action-menu admin-outing-confirm-menu">
                                        <summary class="admin-action-button admin-action-button--confirm" aria-label="{{ $outing->confirmed_slot_id ? 'Changer la validation de '.$outing->title : 'Valider '.$outing->title }}" title="{{ $outing->confirmed_slot_id ? 'Changer ma validation' : 'Valider la sortie' }}">
                                            <i class="fa-solid {{ $outing->confirmed_slot_id ? 'fa-arrows-rotate' : 'fa-check' }}"></i>
                                        </summary>
                                        <div>
                                            <span class="admin-outing-confirm-menu__title">{{ $outing->confirmed_slot_id ? 'Changer ma validation' : 'Valider la sortie' }}</span>
                                            @forelse($confirmableSlots as $slot)
                                                    <form action="{{ route('admin.sorties.confirm', $outing) }}" method="post" data-real-form>
                                                        @csrf
                                                        @method('patch')
                                                        <input type="hidden" name="slot_id" value="{{ $slot['id'] }}">
                                                        <button type="submit" @class(['is-active' => $outing->confirmed_slot_id === $slot['id']])>
                                                            <span>
                                                                <strong>{{ \Carbon\Carbon::parse($slot['date'])->translatedFormat('d M') }} a {{ $slot['time'] }}</strong>
                                                                <small>{{ $slot['votes']->count() }}/{{ $outing->places }} inscrit{{ $slot['votes']->count() > 1 ? 's' : '' }}{{ $slot['names'] ? ' - '.$slot['names'] : '' }}</small>
                                                            </span>
                                                        </button>
                                                    </form>
                                            @empty
                                                <span class="admin-outing-confirm-menu__empty">Aucun créneau avec inscrit.</span>
                                            @endforelse
                                        </div>
                                    </details>
                                    @if($canDeleteOutings)
                                        <form action="{{ route('admin.sorties.destroy', $outing) }}" method="post" data-real-form>
                                            @csrf
                                            @method('delete')
                                            <button class="admin-action-button admin-action-button--delete" type="submit" aria-label="Mettre {{ $outing->title }} à la corbeille" title="Corbeille">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="admin-table-empty-row">
                            <td colspan="6">
                                <div class="admin-empty-state">
                                    <i class="fa-solid fa-users"></i>
                                    <strong>Aucune sortie</strong>
                                    <span>Les sorties créées apparaîtront ici et sur le front.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('partials.admin-pagination', ['paginator' => $outings])

        @if($confirmedOuting && $confirmedOuting->confirmedSlotDetails())
            <section class="admin-outing-confirmed-card" aria-label="Prochaine sortie validée">
                @php
                    $confirmedSlot = $confirmedOuting->confirmedSlotDetails();
                    $confirmedVotes = $confirmedOuting->confirmedVotes();
                @endphp
                <div class="admin-outing-confirmed-card__intro">
                    <span class="admin-outing-confirmed-card__eyebrow">Prochaine sortie validée</span>
                    <h2>{{ $confirmedOuting->title }}</h2>
                    <p>{{ $confirmedOuting->description ?: 'Aucune description.' }}</p>
                </div>
                <div class="admin-outing-confirmed-card__slot">
                    <span>{{ \Carbon\Carbon::parse($confirmedSlot['date'])->translatedFormat('l j F') }}</span>
                    <strong>{{ $confirmedSlot['time'] }}</strong>
                </div>
                <div class="admin-outing-confirmed-card__players">
                    <strong>Joueurs à inviter</strong>
                    <div>
                        @forelse($confirmedVotes as $vote)
                            <span>{{ $vote->user?->name ?? 'Membre supprimé' }}</span>
                        @empty
                            <span>Aucun joueur sur ce créneau.</span>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif
    </section>
</main>
@endsection
