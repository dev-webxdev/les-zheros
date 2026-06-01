@extends('layouts.admin')

@section('title', 'Corbeille Mot Mystere | Les Zheros')
@section('description', 'Elements supprimes du jeu Mot Mystere.')
@php($activeAdmin = 'admin-word-mystery')

@section('admin')
<main class="admin-main">
    @component('admin.components.page-header', ['breadcrumb' => 'Mot Mystere / Corbeille'])
        @slot('actions')
            @if($canForceDeleteWordMystery && ($words->isNotEmpty() || $rewards->isNotEmpty()))
                <form action="{{ route('admin.mot-mystere.empty-trash') }}" method="post" data-real-form>
                    @csrf
                    @method('delete')
                    <button class="admin-danger-button" type="submit">
                        <i class="fa-regular fa-trash-can"></i>
                        <span>Vider la corbeille</span>
                    </button>
                </form>
            @endif
            @component('admin.components.button', ['href' => route('admin.mot-mystere.index'), 'class' => 'admin-secondary-button', 'icon' => 'fa-solid fa-arrow-left', 'label' => 'Retour'])@endcomponent
        @endslot
    @endcomponent

    <section class="admin-content">
        <div class="admin-title">
            <i class="fa-regular fa-trash-can"></i>
            <h1>Corbeille Mot Mystere</h1>
        </div>

        <div class="admin-title admin-word-trash-section-title">
            <i class="fa-solid fa-key"></i>
            <h1>Mots supprimes</h1>
        </div>

        @component('admin.components.table-card')
            @component('admin.components.table', ['class' => 'admin-table--word-mystery'])
                <thead>
                    <tr>
                        <th>Mot</th>
                        <th>Difficulte</th>
                        <th>Date</th>
                        <th>Gain base</th>
                        <th>Essais</th>
                        <th>Supprime le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($words as $word)
                        <tr>
                            <td>
                                <div class="admin-announcement-cell">
                                    <strong>{{ $word->word }}</strong>
                                    <span>{{ $word->hint }}</span>
                                </div>
                            </td>
                            <td>@component('admin.components.badge', ['label' => $word->difficultyLabel()])@endcomponent</td>
                            <td>{{ $word->active_date?->format('d/m/Y') ?? 'Tous les jours' }}</td>
                            <td>{{ number_format($word->reward_base, 0, ',', ' ') }} kamas</td>
                            <td>{{ $word->attempts_count }}</td>
                            <td>{{ $word->deleted_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                @component('admin.components.trash-actions', [
                                    'restoreUrl' => route('admin.mot-mystere.words.restore', $word->id),
                                    'deleteUrl' => route('admin.mot-mystere.words.force-delete', $word->id),
                                    'canDelete' => $canForceDeleteWordMystery,
                                    'restoreAria' => 'Restaurer '.$word->word,
                                    'deleteAria' => 'Supprimer definitivement '.$word->word,
                                ])@endcomponent
                            </td>
                        </tr>
                    @empty
                        @component('admin.components.table-empty-row', ['colspan' => 7])
                            @component('admin.components.empty-state', ['icon' => 'fa-regular fa-trash-can', 'title' => 'Aucun mot en corbeille', 'text' => 'Les mots supprimes apparaitront ici.'])@endcomponent
                        @endcomponent
                    @endforelse
                </tbody>
            @endcomponent
        @endcomponent
        @include('partials.admin-pagination', ['paginator' => $words])

        <div class="admin-title" style="margin-top: 28px;">
            <i class="fa-solid fa-coins"></i>
            <h1>Recompenses supprimees</h1>
        </div>

        @component('admin.components.table-card')
            @component('admin.components.table', ['class' => 'admin-table--word-mystery-rewards'])
                <thead>
                    <tr>
                        <th>Joueur</th>
                        <th>Mot</th>
                        <th>Difficulte</th>
                        <th>Essais</th>
                        <th>Gain</th>
                        <th>Statut</th>
                        <th>Supprime le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rewards as $reward)
                        <tr>
                            <td><strong>{{ $reward->user?->name ?? 'Utilisateur supprime' }}</strong></td>
                            <td><strong>{{ $reward->attempt?->word?->word ?? '-' }}</strong></td>
                            <td>{{ $reward->attempt?->word?->difficultyLabel() ?? $reward->attempt?->difficulty }}</td>
                            <td>{{ $reward->attempt?->attempts_count ?? '-' }}</td>
                            <td>{{ number_format($reward->amount, 0, ',', ' ') }} kamas</td>
                            @php($rewardStatusClass = match ($reward->status) {
                                'paid' => 'admin-tag--success',
                                'cancelled' => 'admin-tag--danger',
                                default => 'admin-tag--warning',
                            })
                            <td>@component('admin.components.badge', ['class' => $rewardStatusClass, 'label' => \App\Models\WordMysteryReward::STATUSES[$reward->status] ?? $reward->status])@endcomponent</td>
                            <td>{{ $reward->deleted_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                @component('admin.components.trash-actions', [
                                    'restoreUrl' => route('admin.mot-mystere.rewards.restore', $reward->id),
                                    'deleteUrl' => route('admin.mot-mystere.rewards.force-delete', $reward->id),
                                    'canDelete' => $canForceDeleteWordMystery,
                                    'restoreAria' => 'Restaurer la recompense',
                                    'deleteAria' => 'Supprimer definitivement la recompense',
                                ])@endcomponent
                            </td>
                        </tr>
                    @empty
                        @component('admin.components.table-empty-row', ['colspan' => 8])
                            @component('admin.components.empty-state', ['icon' => 'fa-regular fa-trash-can', 'title' => 'Aucune recompense en corbeille', 'text' => 'Les recompenses supprimees apparaitront ici.'])@endcomponent
                        @endcomponent
                    @endforelse
                </tbody>
            @endcomponent
        @endcomponent
        @include('partials.admin-pagination', ['paginator' => $rewards])
    </section>
</main>
@endsection
